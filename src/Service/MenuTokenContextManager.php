<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\token\TokenInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * {@inheritdoc}
 */
class MenuTokenContextManager {


  protected $tokenService;
  protected $contextRepository;
  protected $tokenEntityMapper;
  protected $state;
  protected $entityTypeManager;
  protected $menuTokenMenuLinkManager;
  protected $contectualReplacmentLinks;

  /**
   * {@inheritdoc}
   */
  public function __construct(TokenInterface $tokenService, ContextRepositoryInterface $c, TokenEntityMapperInterface $tem, EntityTypeManagerInterface $en, StateInterface $state, MenuLinkManagerInterface $mlm) {

    $this->tokenService = $tokenService;
    $this->contextRepository = $c;
    $this->tokenEntityMapper = $tem;
    $this->entityTypeManager = $en;
    $this->state = $state;
    $this->menuTokenMenuLinkManager = $mlm;

    $this->contectualReplacmentLinks = unserialize($this->state->get('menu_token_links_contectual_replacments'));

    if (empty($contectualReplacmentLinks)) {

      $this->contectualReplacmentLinks = [];

    }

  }

  /**
   * {@inheritdoc}
   */
  public function prepareContectualLinks($relevantLink, $config) {

    $this->contectualReplacmentLinks = unserialize($this->state->get('menu_token_links_contectual_replacments'));

    $uuIdFromLink = substr($relevantLink['id'], strpos($relevantLink['id'], ":") + 1, strlen($relevantLink['id']));

    $text_tokens = $this->tokenService->scan($relevantLink["url"]);

    $text_tokens = array_merge($text_tokens, $this->tokenService->scan($relevantLink["title"]));

    $useInContext = FALSE;

    foreach ($text_tokens as $tokenType => $tokens) {
      $entityType = $this->tokenEntityMapper->getEntityTypeForTokenType($tokenType);

      if (empty($config[$entityType][0]) || $config[$entityType][0] === "context") {

        $useInContext = TRUE;

      }

    }

    if ($useInContext) {

      $this->contectualReplacmentLinks[$uuIdFromLink] = [
        "link" => $relevantLink,
        "config" => $config,
      ];
    }
    else {

      unset($this->contectualReplacmentLinks[$uuIdFromLink]);

    }

    $this->state->set('menu_token_links_contectual_replacments', serialize($this->contectualReplacmentLinks));

  }

  /**
   * {@inheritdoc}
   */
  public function removeFromState($uuIdFromLink) {

    unset($this->contectualReplacmentLinks[$uuIdFromLink]);

  }


  /**
   * {@inheritdoc}
   */
  protected function replaceToken($tokenType, $tokenArray, array $data, array $options) {

    if (!empty($options['configuration'])) {

      $token_service = \Drupal::token();
      $configuration = $options['configuration'];

      // Flag to know what to do with tokens where replacement is not found.
      $removeIfNotPresent = !empty($configuration['remove_if_replacement_is_not_present']) && $configuration['remove_if_replacement_is_not_present'] == 1;
      $entityType = \Drupal::service('token.entity_mapper')->getEntityTypeForTokenType($tokenType);

      $tokenReplacer = \Drupal::service('menu_token.token_replacer');
      $token = array_pop($tokenArray);

      if (!empty($configuration[$entityType][0])) {
        // Make type agnostic so it can handle any type.
        switch ($configuration[$entityType][0]) {

          case "context":

            $replacement = $tokenReplacer->replaceContext($token);

            if (empty($replacement) && $removeIfNotPresent) {

              $replacement = [array_pop($tokenArray) => ''];
            }
            break;

          default:
            break;

        }

      }
      else {

        $replacement = $tokenReplacer->replaceExoticToken($token);

      }

      return $replacement;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function replaceContectualLinks() {

    $contectualReplacmentLinks = unserialize($this->state->get('menu_token_links_contectual_replacments'));

    if (empty($contectualReplacmentLinks)) {
      return TRUE;
    }

    foreach ( $contectualReplacmentLinks as $key => $linkData) {

     $titleTokens = $this->tokenService->scan($contectualReplacmentLinks[$key]["link"]["title"]);

     foreach ($titleTokens as $type => $token) {

       $replacment = $this->replaceToken($type, $token,[],["configuration" => $linkData["config"]]);
        $m = 0;
     }

     $a = 0;
      //$links[$key]["url"]["url"] = $token_service->replace($linkData["url"], [], ["configuration" => $linkData["config"]]);
      //$links[$key]["url"]["title"] = $token_service->replace($linkData["title"], [], ["configuration" => $linkData["config"]]);
    }



    $this->menuTokenMenuLinkManager->rebuildMenuToken($contectualReplacmentLinks);

  }

}
