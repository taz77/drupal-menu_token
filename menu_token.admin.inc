<?php

/**
 * Implements hook_form_FORM_ID_alter().
 */
function menu_token_form_menu_edit_item_alter(&$form, &$form_state) {
  // Load variables
  $menu_token_enabled = variable_get('menu_token_enabled', array());
  $handlers_by_token = menu_token_get_handlers_by_token();

  // Set order for form elements
  $form['link_title']['#weight'] = -1;
  $form['link_path']['#weight'] = -1;

  // Replace fake path (<front>) with user inputed one.
  if (isset($menu_token_enabled[$form['mlid']['#value']])) {
    $form['link_path']['#default_value'] = $menu_token_enabled[$form['mlid']['#value']];
  }

  // Create new fieldset
  $form['menu_token'] = array(
    '#title' => t('tokens'),
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
    '#weight' => 0,
  );

  // Show menu token enable checkbox
  $form['menu_token']['token_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use tokens in title and in path.'),
    '#default_value' => isset($menu_token_enabled[$form['mlid']['#value']]),
  );

  // Show options for handlers groupped by token type
  foreach ($handlers_by_token as $token_type => $handlers) {
    // Get default handler type for token
    $default_handler = variable_get("menu_token_{$token_type}_token_handler", '');

    // Get available handlers for token
    $handlers_available = array();
    $handlers_available[''] = t('Disabled');
    foreach ($handlers as $handler) {
      $handlers_available[$handler['handler_name']] = $handler['handler_title'] . ': ' . $handler['handler_description'];
    }

    $form['menu_token'][$token_type]  = array(
      '#title' => t($token_type . ' tokens'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => empty($default_handler),
    );

    $form['menu_token'][$token_type]["menu_token_{$token_type}_token_handler"] = array(
      '#title' => t('Provide object callback'),
      '#type' => 'radios',
      '#default_value' => $default_handler,
      '#options' => $handlers_available,
    );
  }

  // Token substitutions help.
  $form['menu_token']['token_help'] = array(
    '#title' => t('Replacement patterns'),
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['menu_token']['token_help']['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array_merge(array_keys($handlers_by_token), array('global')),
  );

  // Add custom validation and submit functions.
  array_unshift($form['#validate'], 'menu_token_form_menu_edit_item_validate');
  $form['#submit'][] = 'menu_token_form_menu_edit_item_submit';
}


/**
 * Custom validation for form menu_edit_item.
 */
function menu_token_form_menu_edit_item_validate($form, &$form_state) {
  // If token replacing enabled.
  if (!$form_state['values']['token_enabled']) {
    return;
  }
  $path = $form_state['values']['link_path'];

  // Substitute link_path with fake well known '<front>'.
  $form_state['values']['link_path'] = '<front>';
  $form_state['values']['token_link_path'] = $path;
  // Set 'alter' option to use hook_translated_menu_link().
  $form_state['values']['options']['alter'] = TRUE;

  // Check whether path is external.
  $substituted_path = token_replace($path);
  if (url_is_external($substituted_path)) {
    return;
  }

  // Check access rights for current author to create this menu item.
  /*if ($menu_item = menu_get_item($substituted_path)) {
    if ($menu_item['access']) {
      return;
    }
  }*/
  //form_set_error('link_path', t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $path)));
}


/**
 * Custom submit function for form menu_edit_form.
 */
function menu_token_form_menu_edit_item_submit($form, &$form_state) {
  $menu_token_enabled = variable_get('menu_token_enabled', array());
  $mlid = $form_state['values']['mlid'];
  if (!$form_state['values']['token_enabled']) {
    unset($menu_token_enabled[$mlid]);
  }
  else {
    $menu_token_enabled[$mlid] = $form_state['values']['token_link_path'];
  }


  drupal_set_message($form_state['values']['link_path']);
  drupal_set_message($form_state['values']['token_link_path']);

  $handlers_by_token = menu_token_get_handlers_by_token();
  foreach (array_keys($handlers_by_token) as $token_type) {
    variable_set("menu_token_{$token_type}_token_handler", $form_state['values']["menu_token_{$token_type}_token_handler"]);
  }

  variable_set('menu_token_enabled', $menu_token_enabled);
}

?>
