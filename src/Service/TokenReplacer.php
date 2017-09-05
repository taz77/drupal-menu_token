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
 * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function replaceNone($token) {

    $replacement = [$token => ''];

    return $replacement;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceContext($token, $key, BubbleableMetadata $b) {

    $tokenType = $this->getTokenType($token);
    $entityType = $this->tokenEntityMapper->getEntityTypeForTokenType($tokenType);

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    // If there is no entity type we are in trouble..
    if ($entityType === FALSE) {

      return "";
    }

    $contextsDef = $this->contextRepository->getAvailableContexts();
    $realC = $this->contextRepository->getRuntimeContexts(array_keys($contextsDef));

    foreach ($realC as $keyI => $realCI) {

      $contextDataDefinitionType = $realCI->getContextData()->getPluginDefinition();
      $value = $realCI->getContextData()->getValue();


      // If value is empty and there is no context.
      // We use first value just to make menu system work.
      if (empty($value)) {


        switch ($entityType) {
          case "user":
            $value = User::load(\Drupal::currentUser()->id());
            break;

          default:
            continue;

        }
      }

      if ($contextDataDefinitionType["id"] == "entity" && method_exists($value, "getEntityTypeId") && $value->getEntityTypeId() == $entityType) {

        if (!empty($value)) {
          $rVar = $value;

          if (is_array($rVar)) {
            $rVar = array_pop($rVar);
          }

          $replacement = $this->tokenService->generate($tokenType, [$key => $token], [$tokenType => $rVar], [], $b);

          return $replacement;
        }
      }
    }

    return "";

  }

  /**
   * {@inheritdoc}
   */
  public function replaceRandom($token, $key, BubbleableMetadata $b) {

    $tokenType = $this->getTokenType($token);
    $entityType = $this->tokenEntityMapper->getEntityTypeForTokenType($tokenType);

    $query = \Drupal::entityQuery($entityType);
    $userIds = $query->execute();

    // Pick one random user.
    $randomId = array_rand($userIds, 1);

    $randomVar = $this->entityTypeManager->getStorage($entityType)
      ->load($randomId);

    $replacement = $this->tokenService->generate($tokenType, [$key => $token], [$tokenType => $randomVar], [], $b);


    return $replacement;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceUserDefined($token, $key, $value, BubbleableMetadata $b) {

    $tokenType = $this->getTokenType($token);
    $entityType = $this->tokenEntityMapper->getEntityTypeForTokenType($tokenType);

    $dynamicVar = \Drupal::entityTypeManager()->getStorage($entityType)
      ->load($value);

    $replacement = $this->tokenService->generate($tokenType, [$key => $token], [$tokenType => $dynamicVar], [], $b);

    return $replacement;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceExoticToken($token, $key, BubbleableMetadata $b) {

    $tokenType = $this->getTokenType($token);

    $data = [];
    switch ($tokenType) {

      case "url":
        $data["url"] = Url::createFromRequest(\Drupal::request());
        break;

      default:
        break;

    }

    $b->addCacheContexts(["url"]);
    $b->addCacheContexts(["user"]);

    // Exotic tokens...
    $replacement = $this->tokenService->generate($tokenType, [$key => $token], $data, [], $b);

    return $replacement;
  }

}
