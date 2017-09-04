<?php

namespace Drupal\menu_token\Entity;

use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Defines the menu link content entity class.
 *
 * @property \Drupal\link\LinkItemInterface link
 * @property \Drupal\Core\Field\FieldItemList rediscover
 *
 * @ContentEntityType(
 *   id = "menu_link_content",
 *   label = @Translation("Custom menu link"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\menu_link_content\MenuLinkContentStorageSchema",
 *     "access" = "Drupal\menu_link_content\MenuLinkContentAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\menu_link_content\Form\MenuLinkContentForm",
 *       "delete" = "Drupal\menu_link_content\Form\MenuLinkContentDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer menu",
 *   base_table = "menu_link_content",
 *   data_table = "menu_link_content_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "bundle" = "bundle"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/menu/item/{menu_link_content}/edit",
 *     "edit-form" = "/admin/structure/menu/item/{menu_link_content}/edit",
 *     "delete-form" = "/admin/structure/menu/item/{menu_link_content}/delete",
 *   }
 * )
 */
class MenuTokenLinkContent extends MenuLinkContent {

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = [];
    $definition['class'] = 'Drupal\menu_token\Plugin\Menu\MenuTokenLinkContent';
    $definition['menu_name'] = $this->getMenuName();

    if ($url_object = $this->getUrlObject()) {
      $definition['url'] = NULL;
      $definition['route_name'] = NULL;
      $definition['route_parameters'] = [];
      if (!$url_object->isRouted()) {
        $definition['url'] = $url_object->getUri();
      }
      else {
        $definition['route_name'] = $url_object->getRouteName();
        $definition['route_parameters'] = $url_object->getRouteParameters();
      }
      $definition['options'] = $url_object->getOptions();
    }

    $definition['title'] = $this->getTitle();
    $definition['description'] = $this->getDescription();
    $definition['weight'] = $this->getWeight();
    $definition['id'] = $this->getPluginId();
    $definition['metadata'] = ['entity_id' => $this->id()];
    $definition['form_class'] = '\Drupal\menu_link_content\Form\MenuLinkContentForm';
    $definition['enabled'] = $this->isEnabled() ? 1 : 0;
    $definition['expanded'] = $this->isExpanded() ? 1 : 0;
    $definition['provider'] = 'menu_link_content';
    $definition['discovered'] = 0;
    $definition['parent'] = $this->getParentId();

    return $definition;
  }

}
