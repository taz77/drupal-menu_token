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
  public function clear() {

    $this->contectualReplacmentLinks = [];

    $this->state->set('menu_token_links_contectual_replacments', serialize($this->contectualReplacmentLinks));
  }

  /**
   * {@inheritdoc}
   */
  public function replaceContectualLinks() {

    $contectualReplacmentLinks = unserialize($this->state->get('menu_token_links_contectual_replacments'));

    if (empty($contectualReplacmentLinks)) {
      return TRUE;
    }

    $this->menuTokenMenuLinkManager->rebuildMenuToken($contectualReplacmentLinks);

  }

}
