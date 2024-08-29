<?php

namespace Drupal\geolocation_baidu\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\LayerFeatureBase;

/**
 * Provides marker infowindow.
 *
 * @LayerFeature(
 *   id = "baidu_marker_infowindow",
 *   name = @Translation("Marker InfoWindow"),
 *   description = @Translation("Open InfoWindow on Marker click."),
 *   type = "baidu",
 * )
 */
class BaiduMarkerInfoWindow extends LayerFeatureBase {}
