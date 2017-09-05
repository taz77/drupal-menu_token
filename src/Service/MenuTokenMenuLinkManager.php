<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Manages discovery, instantiation, and tree building of menu link plugins.
 *
 * This manager finds plugins that are rendered as menu links.
 */
class MenuTokenMenuLinkManager extends MenuLinkManager {

  /**
   * {@inheritdoc}
   */
  public function rebuildMenuToken($definitions) {

    try {

      $this->moduleHandler->invoke("menu_token", "prepare_context_replacment", [&$definitions]);

    }
    catch (\Exception $e) {

    }

    $mtts = \Drupal::service('menu_token.tree_storage');
    $mtts->rebuildNonDestructive($definitions);


  }

  /**
   * {@inheritdoc}
   */
  public function getMenuTreeStorage() {
    return $this->treeStorage;
  }

}
