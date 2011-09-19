<?php

/**
 * @file
 * Contains the admin forms and form related functions.
 */


/**
 * Implementation of hook_form_FORM_ID_alter().
 */
function menu_token_form_menu_edit_item_alter(&$form, &$form_state) {
  if ($form['module']['#value'] == 'menu') {
    $types = menu_token_get_plugin_types();
    $options = $form['options']['#value'];

    // Replace fake path (<front>) with user inputed one.
    if (!empty($options['menu_token_link_path'])) {
      $form['link_path']['#default_value'] = $options['menu_token_link_path'];
    }

    $form['link_title']['#weight'] = -5;
    $form['link_path']['#weight'] = -4;

    $form['menu_token_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use tokens in title and in path.'),
      '#default_value' => !empty($options['menu_token_link_path']),
      '#weight' => -3,
    );

    $form['menu_token_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Menu Token options'),
      '#collapsible' => TRUE,
      '#weight' => -2,
      '#states' => array(
        'visible' => array(
          ':input[name="menu_token_enabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    foreach ($types as $type => $items) {
      $info = token_get_info($type);
      $default = NULL;

      if (isset($form_state['values']['menu_token_type_' . $type])) {
        $default = $form_state['values']['menu_token_type_' . $type];
      }
      else if (!empty($options['menu_token_data'][$type])) {
        $default = $options['menu_token_data'][$type]['plugin'];
      }

      $default = isset($default) && in_array($default, array_keys($items)) ? $default : NULL;

      $form['menu_token_options'][$type] = array(
        '#type' => 'container',
      );

      $form['menu_token_options'][$type]['menu_token_type_' . $type] = array(
        '#type' => 'select',
        '#title' => t('Method for') . ' ' . $info['name'],
        '#description' => $info['description'],
        '#options' => array('_none' => t('Disabled')),
        '#default_value' => isset($default) ? $default : array('_none'),
        '#ajax' => array(
          'callback' => 'menu_token_method_callback',
          'wrapper' => 'menu-token-method-options-' . $type,
          'method' => 'replace',
          'effect' => 'fade',
        ),
      );

      foreach ($items as $name => $label) {
        $form['menu_token_options'][$type]['menu_token_type_' . $type]['#options'][$name] = $label;
      }

      $form['menu_token_options'][$type]['menu_token_method_options_wrapper'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="menu-token-method-options-' . $type . '">',
        '#suffix' => '</div>',
      );

      if (isset($default) && $handler = menu_token_get_handler($default)) {
        if ($append = $handler->form_options($options['menu_token_data'][$type]['options'])) {
          $form['menu_token_options'][$type]['menu_token_method_options_wrapper']['menu_token_method_options'] = array(
            '#type' => 'fieldset',
            '#title' => t('Method options'),
            '#collapsible' => TRUE,
          ) + $append;
        }
      }
    }

    // Create new fieldset
    $form['menu_token_replacement_patterns'] = array(
      '#type' => 'fieldset',
      '#title' => t('Replacement patterns'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#weight' => -1,
      '#states' => array(
        'visible' => array(
          ':input[name="menu_token_enabled"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['menu_token_replacement_patterns']['patterns'] = array(
      '#theme' => 'token_tree',
      '#token_types' => array_keys($types),
    );

    // Add custom validation and submit functions.
    array_unshift($form['#validate'], 'menu_token_form_menu_edit_item_validate');
    array_unshift($form['#submit'], 'menu_token_form_menu_edit_item_submit');

    foreach (array_keys(menu_token_get_plugins()) as $plugin) {
      if ($handler = menu_token_get_handler($plugin)) {
        $handler->form_alter($form, $form_state);
      }
    }
  }
}


/**
 * Custom validation for form menu_edit_item.
 */
function menu_token_form_menu_edit_item_validate($form, &$form_state) {
  $values = &$form_state['values'];

  // If token replacing is enabled and this is a custom menu item.
  if ($values['module'] == 'menu' && !empty($values['menu_token_enabled'])) {
    // Store the actual path in the options array.
    $values['options']['menu_token_link_path'] = $values['link_path'];
    $values['options']['menu_token_data'] = array();

    foreach (array_keys(menu_token_get_plugin_types()) as $type) {
      if (!empty($values['menu_token_type_' . $type]) && $values['menu_token_type_' . $type] != '_none') {
        $plugin = $values['menu_token_type_' . $type];

        if ($handler = menu_token_get_handler($plugin)) {
          $values['options']['menu_token_data'][$type] = array(
            'type' => $type,
            'plugin' => $plugin,
            'options' => array(),
          );

          // Validate the form via the handler.
          if ($output = $handler->form_validate($form, $form_state)) {
            // We append the return value of the handler's form validator to
            // the options array.
            $values['options']['menu_token_data'][$type]['options'] += $output;
          }
        }
      }
    }

    // Substitute link_path with fake well known '<front>'.
    $values['link_path'] = '<front>';
  }
  else {
    // Use unset() rather than setting to empty string to avoid redundant
    // serialized data being stored.
    unset($values['options']['attributes']['menu_token_link_path'], $values['options']['menu_token_data']);
  }
}


/**
 * Custom submit for form menu_edit_item.
 */
function menu_token_form_menu_edit_item_submit($form, &$form_state) {
  $values = &$form_state['values'];

  // If token replacing is enabled and this is a custom menu item.
  if ($values['module'] == 'menu' && !empty($values['menu_token_enabled'])) {
    foreach (array_keys(menu_token_get_plugin_types()) as $type) {
      if (!empty($values['menu_token_type_' . $type]) && $values['menu_token_type_' . $type] != '_none') {
        $plugin = $values['menu_token_type_' . $type];

        if ($handler = menu_token_get_handler($plugin)) {
          // Validate the form via the handler.
          if ($output = $handler->form_submit($form, $form_state)) {
            $values['options']['menu_token_data'][$type]['options'] += $output;
          }
        }
      }
    }
  }
}


/**
 * Implementation hook_form_FORM_ID_alter().
 */
function menu_token_form_menu_overview_form_alter(&$form, $form_state) {
  foreach ($form as &$item) {
    if (isset($item['mlid'], $item['#item']['options']) && isset($item['#item']['options']['menu_token_link_path'])) {
      $item['title']['#markup'] .= theme('menu_token_uses_tokens');
    }
  }
}


/**
 * Ajax callback for the method select dropdown.
 */
function menu_token_method_callback($form, $form_state) {
  $parents = $form_state['triggering_element']['#array_parents'];

  array_pop($parents);
  array_push($parents, 'menu_token_method_options_wrapper');

  return drupal_array_get_nested_value($form, $parents);
}
