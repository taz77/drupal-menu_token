<?php

class menu_token_node_user_defined implements menu_token_handler {
  function form_options($form_state) {
    $output['menu_token_node_user_defined'] = array(
      '#title' => t('Node ID'),
      '#description' => t('The node id of the node that this token handler should load.'),
      '#type' => 'textfield',
      '#default_value' => '',
    );

    return $output;
  }

  function form_submit($form, &$form_state) {
    // Nothing to do here.
  }

  function form_validate($form, &$form_state) {
    // Nothing to do here.
  }

  function form_alter(&$form, &$form_state) {
    // Nothing to do here.
  }

  function object_load($options) {
    // Nothing to do here.
  }
}
