<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;

/**
 * Provides marker infowindow.
 *
 * @MapFeature(
 *   id = "map_disable_poi",
 *   name = @Translation("Disable POIs"),
 *   description = @Translation("Disable points of interest feature. Attention: May interfere with MapStyle."),
 *   type = "google_maps",
 * )
 */
class GoogleMapDisablePOI extends MapFeatureBase {

}
