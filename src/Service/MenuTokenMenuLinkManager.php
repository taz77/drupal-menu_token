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
  public function rebuildByMenuName($menuName) {

    $definitions = $this->treeStorage->loadByProperties(['menu_name' => $menuName]);
    $connection = \Drupal::database();


    foreach ($definitions as $plugin_id => &$definition) {

      if (strpos($plugin_id, 'menu_link_content:') === 0) {
        // Have to overide drupal default.
        $data = explode(":", $plugin_id);
        $query = $connection->select("menu_link_content", 'ml');
        $query->join('menu_link_content_data', 'mlcd', 'mlcd.id = ml.id');
        $query->addField('mlcd', 'title');
        $query->addField('mlcd', 'link__uri');
        $query->condition('ml.bundle', 'menu_link_content');
        $query->condition('ml.uuid', $data[1], 'IN');
        $query->distinct(TRUE);
        $result = $query->execute()->fetch();
        $definition["title"] = $result->title;
        $definition["url"] = $result->link__uri;
      }

    }

    $this->moduleHandler->invoke("menu_token", "menu_links_discovered_alter", [&$definitions]);

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

    $this->treeStorage->rebuildForMenuToken($definitions);
  }

}
