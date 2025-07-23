<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Attribute\MapFeature;
use Drupal\geolocation\Plugin\geolocation\MapFeature\GeolocationFieldWidgetMapConnector;

/**
 * Provides Recenter control element.
 */
#[MapFeature(
  id: 'google_maps_geometry_widget_map_connector',
  name: new \Drupal\Core\StringTranslation\TranslatableMarkup('Leaflet Geometry Widget Map Connector'),
  description: new \Drupal\Core\StringTranslation\TranslatableMarkup('Internal use.'),
  type: 'all',
  hidden: TRUE,
)]
class GoogleGeometryWidgetMapConnector extends GeolocationFieldWidgetMapConnector {}
