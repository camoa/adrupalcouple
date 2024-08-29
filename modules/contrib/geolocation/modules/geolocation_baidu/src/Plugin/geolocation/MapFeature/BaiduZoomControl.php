<?php

namespace Drupal\geolocation_baidu\Plugin\geolocation\MapFeature;

use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlElementBase;

/**
 * Provides map zoom control support.
 *
 * @MapFeature(
 *   id = "baidu_zoom_control",
 *   name = @Translation("Baidu Zoom control"),
 *   description = @Translation("Add map zoom controls."),
 *   type = "baidu",
 * )
 */
class BaiduZoomControl extends ControlElementBase {}
