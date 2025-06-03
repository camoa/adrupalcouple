<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\MapFeatureBase;

/**
 * Provides marker infowindow.
 */
#[MapFeature(
  id: 'map_disable_poi',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable POIs'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Disable points of interest feature. Attention: May interfere with MapStyle.'),
  type: 'google_maps'
)]
class GoogleMapDisablePOI extends MapFeatureBase {

}
