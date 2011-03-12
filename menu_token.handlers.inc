<?php

/**
 * Implementation of hook_menu_token_handlers
 */
function menu_token_menu_token_handler() {
  $handlers = array();
  $handlers[] = array(
    'token_type' => 'user',
    'handler_name' => 'context_user',
    'handler_title' => t('Context'),
    'handler_description' => t('get user object from url'),
    'handler_object_callback' => 'menu_token_context_user',
  );
  $handlers[] = array(
    'token_type' => 'node',
    'handler_name' => 'context_node',
    'handler_title' => t('Context'),
    'handler_description' => t('get node object from url'),
    'handler_object_callback' => 'menu_token_context_node',
  );
  return $handlers;
}


/* Returns context node object for menu token handler */
function menu_token_context_node() {
  if(arg(0) == 'node' && is_numeric(arg(1))) {
    return node_load(arg(1));
  }
  else {
    return false;
  }
}


/* Returns context user object for menu token handler  */
function menu_token_context_user() {
  if(arg(0) == 'user' && is_numeric(arg(1))) {
    return user_load(arg(1));
  }
  else {
    return false;
  }
}

?>
