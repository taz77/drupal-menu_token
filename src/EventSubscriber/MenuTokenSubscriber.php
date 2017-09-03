<?php

namespace Drupal\menu_token\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event Subscriber MenuTokenSubsciber.
 */
class MenuTokenSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onController(FilterControllerEvent $event) {

    $menuTokenMenuLinkManager = \Drupal::service('menu_token.manager.menu.link');
    $menuTokenMenuLinkManager->rebuild();

    /*foreach (\Drupal::routeMatch()->getParameters() as $param) {

      $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();
      $is_ajax = \Drupal::request()->isXmlHttpRequest();

      if ($param instanceof EntityInterface && $is_admin === FALSE && $is_ajax === FALSE) {

        // Must read from configuration.
        $menuTokenBuildedByType = unserialize(\Drupal::state()->get('menu_token_builded_by_type'));

        if (!empty($menuTokenBuildedByType)) {

          $typeId = $param->getEntityTypeId();

          foreach ($menuTokenBuildedByType as $key => $val) {

            if (in_array($typeId, $val)) {

              $menuTokenMenuLinkManager = \Drupal::service('menu_token.manager.menu.link');
              $menuTokenMenuLinkManager->rebuild();

            }

          }

        }

      }
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // For this example I am using KernelEvents constants
    // (see below a full list).
    $events[KernelEvents::CONTROLLER][] = ['onController'];
    return $events;
  }

}
