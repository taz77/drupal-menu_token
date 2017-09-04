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

    // Menu should be cached if not the user is st...
    // and will suffer performance hit.
    // The is nothing that can be done here.
    $menuTokenMenuLinkManager = \Drupal::service('menu_token.context_manager');
    $menuTokenMenuLinkManager->replaceContectualLinks();


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
