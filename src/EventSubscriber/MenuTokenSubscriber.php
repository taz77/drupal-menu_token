<?php

namespace Drupal\menu_token\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber MenuTokenSubsciber.
 */
class MenuTokenSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onController(FilterControllerEvent $event) {

    // Use cache to avoid duplacate req!
    $cache = \Drupal::cache()->get('menu_token_cached_context');
    $cr = \Drupal::service('context.repository');
    $contextsDef = $cr->getAvailableContexts();
    $realC = $cr->getRuntimeContexts(array_keys($contextsDef));

    if (empty($cache->data) || sha1(serialize($realC)) != sha1(serialize($cache->data))) {

      \Drupal::cache()->set('menu_token_cached_context', $realC, -1, ['menu_token_cached_context_tag']);

      $menuTokenMenuLinkManager = \Drupal::service('menu_token.context_manager');
      $menuTokenMenuLinkManager->replaceContectualLinks();
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[KernelEvents::CONTROLLER][] = ['onController'];
    return $events;
  }

}
