<?php

namespace Drupal\editoria11y\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overrides Cache-Control for the config API endpoint.
 *
 * Drupal's FinishResponseSubscriber sets short cache lifetimes on
 * authenticated responses. This subscriber runs after it to set a long
 * private, immutable cache for browser caching with URL-based versioning.
 */
final class ConfigCacheControlSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // FinishResponseSubscriber runs at priority 0; run after it.
    return [
      KernelEvents::RESPONSE => ['onResponse', -10],
    ];
  }

  /**
   * Sets private, immutable Cache-Control on the config API response.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $route_name = $event->getRequest()->attributes->get('_route');
    if ($route_name !== 'editoria11y.api_config') {
      return;
    }
    $response = $event->getResponse();
    // A 403 must NOT be cached.
    if ($response->getStatusCode() !== 200) {
      return;
    }
    $response->headers->set('Cache-Control', 'private, max-age=2628000, immutable');
  }

}
