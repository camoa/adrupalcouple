<?php

namespace Drupal\geolocation\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;

/**
 * Redraw locations as shapes.
 *
 * @MapFeature(
 *   id = "client_location_indicator",
 *   name = @Translation("Client Location Indicator"),
 *   description = @Translation("Show and permanently update client location."),
 *   type = "all",
 * )
 */
class ClientLocationIndicator extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['icon_path'] = \Drupal::service('extension.list.module')->getPath('geolocation') . '/icons/current_location.png';

    return $settings;
  }

}
