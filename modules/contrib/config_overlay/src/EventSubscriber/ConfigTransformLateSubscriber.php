<?php

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;

/**
 * Reacts late to configuration import and early to configuration export.
 *
 * Note that this subscriber is only active in Config Split is installed.
 *
 * @see \Drupal\config_overlay\EventSubscriber\ConfigTransformEarlySubscriber
 */
class ConfigTransformLateSubscriber extends ConfigTransformSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Running the overlay after Config Split has run allows overlaying
      // shipped configuration for stack-able configuration splits.
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['overlayShipped', -100],
      // Removing shipped configuration early make stack-able configuration
      // splits not split off that configuration.
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => ['removeShipped', 100],
    ];
  }

}
