<?php

namespace Drupal\menu_token\Service;

use Drupal\Core\Menu\MenuLinkManager;

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

      $a = 0;
      die("sdad");
    }

    foreach ($definitions as $plugin_id => &$definition) {

      $definition['id'] = $plugin_id;
      $this->processDefinition($definition, $plugin_id);

    }


    // If this plugin was provided by a module that does not exist, remove the
    // plugin definition.
    // @todo Address what to do with an invalid plugin.
    //   https://www.drupal.org/node/2302623
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if (!empty($plugin_definition['provider']) && !$this->moduleHandler->moduleExists($plugin_definition['provider'])) {
        unset($definitions[$plugin_id]);
      }
    }

    // Apply overrides from config.
    $overrides = $this->overrides->loadMultipleOverrides(array_keys($definitions));
    foreach ($overrides as $id => $changes) {
      if (!empty($definitions[$id])) {
        $definitions[$id] = $changes + $definitions[$id];
      }
    }

    $this->treeStorage->rebuild($definitions);

  }

  /**
   * {@inheritdoc}
   */
  public function getMenuTreeStorage() {
    return $this->treeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function menuNameInUseContent($menu_name) {

    $query = $this->connection->select("menu_link_content", 'ml');
    $query->join('menu_link_content_data', 'mlcd', 'mlcd.id = ml.id');
    $query->addField('mlcd', 'id');
    $query->condition('ml.bundle', 'menu_link_content');
    $query->condition('ml.menu_name', $menu_name, 'IN');
    $query->range(0, 1);

    return (bool) $this->safeExecuteSelect($query);

  }

  /**
   * {@inheritdoc}
   */
  public function getMenuNamesInContent() {

    $query = $this->connection->select("menu_link_content", 'ml');
    $query->join('menu_link_content_data', 'mlcd', 'mlcd.id = ml.id');
    $query->addField('mlcd', 'id');
    $query->condition('ml.bundle', 'menu_link_content');
    $query->distinct();
    return $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);

  }

  /**
   * {@inheritdoc}
   */
  public function getContextMenuItems($menuName) {

    $query = $this->connection->select("menu_link_content", 'ml');
    $query->join('menu_link_content_data', 'mlcd', 'mlcd.id = ml.id');
    $query->addField('mlcd', 'id');
    $query->condition('ml.bundle', 'menu_link_content');
    $query->condition('ml.menu_name', $menuName, 'IN');
  }


}
