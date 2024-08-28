<?php

namespace Drupal\config_overlay\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;

/**
 * Reacts early to configuration import and late to configuration export.
 */
class ConfigTransformEarlySubscriber extends ConfigTransformSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Overlaying the shipped configuration early makes other transformers see
      // the full set of configuration as though Config Overlay was not
      // installed. If Config Split is installed, this allows shipping
      // configuration splits in modules and having them detected in the
      // initial configuration import, in particular.
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => ['overlayShipped', 100],
      ConfigEvents::STORAGE_TRANSFORM_EXPORT => ['removeShipped', -100],
    ];
  }

}
