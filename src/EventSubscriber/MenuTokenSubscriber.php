<?php

namespace Drupal\menu_token\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber MenuTokenSubscriber.
 */
class MenuTokenSubscriber implements EventSubscriberInterface {

  /**
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The controller event.
   */
  public function onController(FilterControllerEvent $event) {
    $current_user_id = \Drupal::currentUser()->id();

    // Use cache to avoid duplicated req!
    $cache = \Drupal::cache()->get('menu_token_cached_context' . $current_user_id);
    $context_repository = \Drupal::service('context.repository');
    $contexts_def = $context_repository->getAvailableContexts();
    $runtime_context = $context_repository->getRuntimeContexts(array_keys($contexts_def));

    if (empty($cache->data) || sha1(serialize($runtime_context)) != sha1(serialize($cache->data))) {
      \Drupal::cache()->set('menu_token_cached_context' . $current_user_id, $runtime_context, -1, ['menu_token_cached_context_tag' . $current_user_id]);
      $menu_token_menu_link_manager = \Drupal::service('menu_token.context_manager');
      $menu_token_menu_link_manager->replaceContextualLinks();
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
