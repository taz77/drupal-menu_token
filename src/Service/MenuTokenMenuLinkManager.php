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

    } catch (\Exception $e) {

    }

    foreach ($definitions as $id => $definition) {

      $tranlatable = new TranslatableMarkup($definition["title"]);
      //$stringT = $tranlatable->render();
      \Drupal::database()->update('menu_tree')
        ->condition('id' , $definition["provider"].":".$id)
        ->fields([
          'url' => $definition["url"],
          'title' => serialize($tranlatable),
        ])
        ->execute();

    }
     $this->resetDefinitions();
     $menuTokenMenuLinkManager = \Drupal::service('cache.menu');
     $menuTokenMenuLinkManager->invalidateAll();
     $this->resetDefinitions();


  }

  /**
   * {@inheritdoc}
   */
  public function getMenuTreeStorage() {
    return $this->treeStorage;
  }

}
