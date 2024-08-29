<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

/**
 * Provides Zoom control element.
 *
 * @MapFeature(
 *   id = "control_zoom",
 *   name = @Translation("Map Control - Zoom"),
 *   description = @Translation("Add button to toggle map type."),
 *   type = "google_maps",
 * )
 */
class GoogleControlZoom extends GoogleControlElementBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    $settings = parent::getDefaultSettings();
    $settings['style'] = 'LARGE';

    return $settings;
  }

}
