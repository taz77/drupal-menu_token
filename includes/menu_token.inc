<?php

/**
 * @file
 * Provides the menu token handler interface and helper functions.
 */


/**
 * The menu token handler interface should be implemented by all menu token plugins.
 */
interface menu_token_handler {
  /**
   * You can provide options for your menu token handler via this function.
   * The return value will be appended to the form as soon as the administrator
   * chooses your plugin.
   */
  function form_options($options);

  /**
   * This function allows your plugin to act upon form submission. The return
   * value will be added to the $options array and thus should be an array itself.
   *
   * Note: Only invoked for selected plugins.
   */
  function form_submit($form, &$form_state);

  /**
   * This function allows your plugin to act upon form validation. The return
   * value will be added to the $options array and thus should be an array itself.
   *
   * Note: Only invoked for selected plugins.
   */
  function form_validate($form, &$form_state);

  /**
   * You can alter the menu item administration form with this function.
   */
  function form_alter(&$form, &$form_state);

  /**
   * This function is used to load the relevant token replacement object.
   */
  function object_load($options);
}


/**
 * Retrieves the handler of a menu token plugin.
 *
 * @param $name
 *   The name of a plugin.
 *
 * @return
 *   A menu_token_handler object that represents the handler of the plugin
 *   defined by $name or FALSE if no plugin named $name exists.
 */
function menu_token_get_handler($name) {
  $handlers = &drupal_static(__FUNCTION__);

  if (!isset($handlers[$name])) {
    if ($plugin = menu_token_get_plugin($name)) {
      $handlers[$name] = new $plugin['class']($plugin);
    }
  }

  if (isset($handlers[$name])) {
    return $handlers[$name];
  }

  return FALSE;
}


/**
 * Retrieves a menu token plugin.
 *
 * @param $name
 *   The name of a plugin.
 *
 * @return
 *   An array containing information about the plugin as returned by the ctools
 *   plugin API.
 */
function menu_token_get_plugin($name) {
  $plugins = _menu_token_plugin_info()->plugins;
  
  if (isset($plugins[$name])) {
    return $plugins[$name];
  }

  return FALSE;
}


/**
 * Retrieves a list of all available menu token plugins.
 *
 * @return
 *   An array containing all available plugins.
 */
function menu_token_get_plugins() {
  return _menu_token_plugin_info()->plugins;
}


/**
 * Retrieves a list of all token types that are covered by the available menu
 * token plugins.
 *
 * @return
 *   An array containing all token types covered by menu token plugins.
 */
function menu_token_get_plugin_types() {
  return _menu_token_plugin_info()->types;
}


/**
 * Builds and returns information about the menu token plugins and their types.
 */
function _menu_token_plugin_info() {
  $cache = &drupal_static(__FUNCTION__);

  if (!isset($cache)) {
    ctools_include('plugins');
    
    $cache = (object) array(
      'plugins' => array(),
      'types' => array(),
    );

    $info = token_get_info();
    
    foreach (ctools_get_plugins('menu_token', 'plugins') as $plugin) {
      if (isset($info['types'][$plugin['type']]) && $class = ctools_plugin_get_class($plugin, 'handler')) {        
        $cache->plugins[$plugin['name']] = $plugin;
        $cache->plugins[$plugin['name']]['class'] = $class;
        $cache->types[$plugin['type']][$plugin['name']] = $plugin['label'];
      }
    }
  }
  
  return $cache;
}
