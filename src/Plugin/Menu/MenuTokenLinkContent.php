<?php

namespace Drupal\menu_token\Plugin\Menu;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

/**
 * Provides the menu link plugin for content menu links.
 */
class MenuTokenLinkContent extends MenuLinkContent {


  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    $parameters = isset($this->pluginDefinition['route_parameters']) ? $this->pluginDefinition['route_parameters'] : [];
    $route = $this->routeProvider()->getRouteByName($this->getRouteName());
    $variables = $route->compile()->getVariables();

    // Normally the \Drupal\Core\ParamConverter\ParamConverterManager has
    // processed the Request attributes, and in that case the _raw_variables
    // attribute holds the original path strings keyed to the corresponding
    // slugs in the path patterns. For example, if the route's path pattern is
    // /filter/tips/{filter_format} and the path is /filter/tips/plain_text then
    // $raw_variables->get('filter_format') == 'plain_text'.

    $raw_variables = $route_match->getRawParameters();

    foreach ($variables as $name) {
      if (isset($parameters[$name])) {
        continue;
      }

      if ($raw_variables && $raw_variables->has($name)) {
        $parameters[$name] = $raw_variables->get($name);
      }
      elseif ($value = $route_match->getRawParameter($name)) {
        $parameters[$name] = $value;
      }
    }
    // The UrlGenerator will throw an exception if expected parameters are
    // missing. This method should be overridden if that is possible.
    return $parameters;
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    $options = $this->getOptions();

    if (!empty($options["bubleble_metadata"])) {
      $bubMetadata = $options["bubleble_metadata"];
      return $bubMetadata->getCacheContexts();

    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
