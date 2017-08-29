<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuTreeStorage;

/**
 * Provides a menu tree storage using the database.
 */
class MenuTokenTreeStorage extends MenuTreeStorage {

  /**
   * {@inheritdoc}
   */
  public function rebuildForMenuToken(array $definitions) {
    $links = [];
    $children = [];
    $top_links = [];

    if ($definitions) {
      foreach ($definitions as $id => $link) {
        // Flag this link as discovered, i.e. saved via rebuild().
        $link['discovered'] = 1;
        // Note: The parent we set here might be just stored in the {menu_tree}
        // table, so it will not end up in $top_links. Therefore the later loop
        // on the orphan links, will handle those cases.
        if (!empty($link['parent'])) {
          $children[$link['parent']][$id] = $id;
        }
        else {
          // A top level link - we need them to root our tree.
          $top_links[$id] = $id;
          $link['parent'] = '';
        }
        $links[$id] = $link;
      }
    }
    foreach ($top_links as $id) {
      $this->saveRecursive($id, $children, $links);
    }
    // Handle any children we didn't find starting from top-level links.
    foreach ($children as $orphan_links) {
      foreach ($orphan_links as $id) {
        // Check for a parent that is not loaded above since only internal links
        // are loaded above.
        $parent = $this->loadFull($links[$id]['parent']);
        // If there is a parent add it to the links to be used in
        // ::saveRecursive().
        if ($parent) {
          $links[$links[$id]['parent']] = $parent;
        }
        else {
          // Force it to the top level.
          $links[$id]['parent'] = '';
        }
        $this->saveRecursive($id, $children, $links);
      }
    }
    $result = $this->findNoLongerExistingLinks($definitions);

    // Remove all such items.
    if ($result) {
      $this->purgeMultiple($result);
    }


    // Reset only what we want!.
    $this->resetDefinitions();
    $affected_menus = $this->getMenuNames();
    // Invalidate any cache tagged with any menu name.
    $cache_tags = Cache::buildTags('config:system.menu', $affected_menus, '.');
    $this->cacheTagsInvalidator->invalidateTags($cache_tags);
  }

}
