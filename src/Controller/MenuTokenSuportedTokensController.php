<?php

namespace Drupal\menu_token\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */
class MenuTokenSuportedTokensController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {

    $availableEntitiesConfiguration = \Drupal::config('menu_token.availableentitiesconfiguration');
    $data = $availableEntitiesConfiguration->getRawData();

    $renderable = [];
    foreach ($data['available_entities'] as $configKey => $configItem) {

      if ($configItem !== 0) {

        $renderable[] = $configKey;
      }
    }

    $token_tree = \Drupal::service('token.tree_builder')->buildRenderable($renderable, [
      'click_insert' => FALSE,
      'show_restricted' => FALSE,
      'show_nested' => FALSE,
    ]);

    $output = '<dt>' . t('The list of the currently available tokens supported by menu_token are shown below.') . '</dt>';
    $output .= '<br /><dd>' . \Drupal::service('renderer')->render($token_tree) . '</dd>';
    $output .= '</dl>';

    $build = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    return $build;
  }

}
