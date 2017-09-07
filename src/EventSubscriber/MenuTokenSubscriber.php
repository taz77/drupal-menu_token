<?php

namespace Drupal\menu_token\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\menu_token\Service\MenuTokenContextManager;

/**
 * Event Subscriber MenuTokenSubscriber.
 */
class MenuTokenSubscriber implements EventSubscriberInterface {


  protected $currentUser;
  protected $cache;
  protected $contextRepository;
  protected $menuTokenContextManager;

  /**
   * MenuTokenSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   Context repository service.
   * @param \Drupal\menu_token\Service\MenuTokenContextManager $menuTokenContextManager
   *   Menu token context manager.
   */
  public function __construct(AccountProxy $currentUser, CacheBackendInterface $cache, ContextRepositoryInterface $contextRepository, MenuTokenContextManager $menuTokenContextManager) {

    $this->currentUser = $currentUser;
    $this->cache = $cache;
    $this->contextRepository = $contextRepository;
    $this->menuTokenContextManager = $menuTokenContextManager;
  }

  /**
   * The CONTROLLER event occurs once a controller was found.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   The controller event.
   *
   *   For handling a request. Constant KernelEvents::CONTROLLER.
   */
  public function onController(FilterControllerEvent $event) {
    $current_user_id = $this->currentUser->id();

    /*
     * Use cache tag named menu_token_cached_context to store the
     * sh1 of serialized sh1 string of context returned by context service.
     * If context on current request is the same as context on previous
     * request then replacement is not executed.
     *
     * If it is not the menu_token.context_manager service takes over
     * and replace the menu links from state object.
     *
     */
    $cache = $this->cache->get('menu_token_cached_context' . $current_user_id);
    $contexts_def = $this->contextRepository->getAvailableContexts();
    $runtime_context = $this->contextRepository->getRuntimeContexts(array_keys($contexts_def));

    if (empty($cache->data) || sha1(serialize($runtime_context)) != sha1(serialize($cache->data))) {
      $this->cache->set('menu_token_cached_context' . $current_user_id, $runtime_context, -1, ['menu_token_cached_context_tag' . $current_user_id]);
      $this->menuTokenContextManager->replaceContextualLinks();
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
