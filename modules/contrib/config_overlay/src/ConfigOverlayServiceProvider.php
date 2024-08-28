<?php

namespace Drupal\config_overlay;

use Drupal\config_overlay\EventSubscriber\ConfigTransformLateSubscriber;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides dynamic services for Config Overlay.
 */
class ConfigOverlayServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['config_split'])) {
      // The default transformer for Config Overlay is run early on import. If
      // Config Split is installed run the same transformation again, after
      // the configuration splits have been processed. Both the overlay and the
      // removal of the shipped configuration is idempotent, so we can safely
      // run them twice.
      $container->register('config_overlay.config_subscriber.transform_late', ConfigTransformLateSubscriber::class)
        ->addArgument(new Reference('extension.list.profile'))
        ->addArgument(new Parameter('install_profile'))
        ->addArgument(new Reference('config_overlay.extension_storage_factory'))
        ->addArgument(new Reference('config.storage'))
        ->addTag('event_subscriber');
    }
  }

}
