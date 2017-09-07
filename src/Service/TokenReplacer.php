<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TokenInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * TokenReplacer class.
 */
class TokenReplacer {

  protected $tokenService;
  protected $contextRepository;
  protected $tokenEntityMapper;
  protected $entityTypeManager;
  protected $url;

  /**
   * {@inheritdoc}
   */
  public function __construct(TokenInterface $tokenService, ContextRepositoryInterface $c, TokenEntityMapperInterface $tem, EntityTypeManagerInterface $en) {
    $this->tokenService = $tokenService;
    $this->contextRepository = $c;
    $this->tokenEntityMapper = $tem;
    $this->entityTypeManager = $en;
  }

  /**
   * @param $token
   *
   * @return mixed
   */
  private function getTokenType($token) {
    preg_match_all('/
      \[             # [ - pattern start
      ([^\s\[\]:]+)  # match $type not containing whitespace : [ or ]
      :              # : - separator
      ([^\[\]]+)     # match $name not containing [ or ]
      \]             # ] - pattern end
      /x', $token, $matches);

    $types = $matches[1];
    return $types[0];
  }

  /**
   * @param $token
   *
   * @return array
   */
  public function replaceNone($token) {
    $replacement = [$token => ''];
    return $replacement;
  }

  /**
   * Replace context.
   *
   * @param $token
   * @param $key
   * @param BubbleableMetadata $b
   *
   * @return array|string
   */
  public function replaceContext($token, $key, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    // If there is no entity type we are in trouble..
    if ($entity_type === FALSE) {
      return "";
    }

    $contexts_def = $this->contextRepository->getAvailableContexts();
    $real_context = $this->contextRepository->getRuntimeContexts(array_keys($contexts_def));

    foreach ($real_context as $key_i => $real_ci) {
      $context_data_definition_type = $real_ci->getContextData()->getPluginDefinition();
      $value = $real_ci->getContextData()->getValue();

      // Service contextRepository does not return value as expected
      // on anonymous users.
      if ($entity_type == "user" && method_exists($value, "isAnonymous") && $value->isAnonymous()) {
        // $value = User::load(\Drupal::currentUser()->id());.
        // Drupal screw me... User will always ask why
        // there are nothing shown for anonymous user..
        // Let them have string Anonymous and they will be happy and quiet.
        return [$token => "Anonymous"];
      }

      if (empty($value)) {
        switch ($entity_type) {
          case "user":
            $value = User::load(\Drupal::currentUser()->id());
            break;

          default:
            continue;
        }
      }

      if ($context_data_definition_type["id"] == "entity" && method_exists($value, "getEntityTypeId") && $value->getEntityTypeId() == $entity_type) {
        if (!empty($value)) {
          $r_var = $value;
          if (is_array($r_var)) {
            $r_var = array_pop($r_var);
          }
          $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $r_var], [], $b);
          return $replacement;
        }
      }
    }
    return "";
  }

  /**
   * @param $token
   * @param $key
   * @param BubbleableMetadata $b
   *
   * @return mixed
   */
  public function replaceRandom($token, $key, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

    $query = \Drupal::entityQuery($entity_type);
    $user_ids = $query->execute();

    // Pick one random user.
    $random_id = array_rand($user_ids, 1);
    $random_user = $this->entityTypeManager->getStorage($entity_type)
      ->load($random_id);

    $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $random_user], [], $b);
    return $replacement;
  }

  /**
   * @param $token
   * @param $key
   * @param $value
   * @param BubbleableMetadata $b
   *
   * @return mixed
   */
  public function replaceUserDefined($token, $key, $value, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);
    $entity_type = $this->tokenEntityMapper->getEntityTypeForTokenType($token_type);

    $entity_object = \Drupal::entityTypeManager()->getStorage($entity_type)
      ->load($value);
    $replacement = $this->tokenService->generate($token_type, [$key => $token], [$token_type => $entity_object], [], $b);
    return $replacement;
  }

  /**
   * @param $token
   * @param $key
   * @param BubbleableMetadata $b
   *
   * @return array
   */
  public function replaceExoticToken($token, $key, BubbleableMetadata $b) {

    $token_type = $this->getTokenType($token);

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    $data = [];
    switch ($token_type) {
      case "url":
        $data["url"] = Url::createFromRequest(\Drupal::request());
        break;

      case "current-user":
        $data["user"] = User::load(\Drupal::currentUser()->id());

        if (method_exists($data["user"], "isAnonymous") && $data["user"]->isAnonymous()) {
          // $value = User::load(\Drupal::currentUser()->id());.
          // Drupal screw me... User will always ask why
          // there are nothing shown for anonymous user..
          // Let them have string Anonymous and they will be happy and quiet.
          return [$token => "Anonymous"];
        }
        break;

      default:
        break;
    }

    // Exotic tokens...
    $replacement = $this->tokenService->generate($token_type, [$key => $token], $data, [], $b);
    return $replacement;
  }

}
