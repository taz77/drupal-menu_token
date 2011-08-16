<?php

interface menu_token_handler {
  function form_options($form_state);

  function form_submit($form, &$form_state);

  function form_validate($form, &$form_state);

  function form_alter($form, $form_state);

  function object_load($item, $map);
}