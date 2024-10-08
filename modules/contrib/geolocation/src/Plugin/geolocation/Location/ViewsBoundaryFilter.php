<?php

namespace Drupal\geolocation\Plugin\geolocation\Location;

use Drupal\geolocation\LocationBase;
use Drupal\geolocation\LocationInterface;
use Drupal\geolocation\ViewsContextTrait;

/**
 * Derive center from proximity filter.
 *
 * @Location(
 *   id = "views_boundary_filter",
 *   name = @Translation("Boundary filter"),
 *   description = @Translation("Set map center from boundary filter."),
 * )
 */
class ViewsBoundaryFilter extends LocationBase implements LocationInterface {

  use ViewsContextTrait;

  /**
   * {@inheritdoc}
   */
  public function getAvailableLocationOptions(array $context = []): array {
    $options = [];

    if ($displayHandler = self::getViewsDisplayHandler($context)) {
      /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
      foreach ($displayHandler->getHandlers('filter') as $delta => $filter) {
        if (
          // @phpstan-ignore-next-line
          $filter->getPluginId() === 'geolocation_filter_boundary'
          && $filter !== $context['views_filter'] ?? NULL
        ) {
          $options[$delta] = $this->t('Boundary filter') . ' - ' . $filter->adminLabel();
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates($location_option_id, array $location_option_settings, $context = NULL): array {
    if (!($displayHandler = self::getViewsDisplayHandler($context))) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    $filter = $displayHandler->getHandler('filter', $location_option_id);
    if (empty($filter)) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    if (
      $filter->value['lat_south_west'] === ""
      || $filter->value['lat_north_east'] === ""
      || $filter->value['lng_south_west'] === ""
      || $filter->value['lng_north_east'] === ""
    ) {
      return parent::getCoordinates($location_option_id, $location_option_settings, $context);
    }

    // See documentation at
    // http://tubalmartin.github.io/spherical-geometry-php/#LatLngBounds
    $latitude = ($filter->value['lat_south_west'] + $filter->value['lat_north_east']) / 2;
    $longitude = ($filter->value['lng_south_west'] + $filter->value['lng_north_east']) / 2;
    if ($filter->value['lng_south_west'] > $filter->value['lng_north_east']) {
      $longitude = $longitude == 0 ? 180 : fmod((fmod((($longitude + 180) - -180), 360) + 360), 360) + -180;
    }

    return [
      'lat' => $latitude,
      'lng' => $longitude,
    ];
  }

}
