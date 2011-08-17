<?php

/**
 * @file
 * Provides the menu token handler interface.
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
  function form_options($form_state);

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