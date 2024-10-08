<?php

namespace Drupal\geolocation_geometry\Plugin\views\argument;

use Drupal\geolocation\Plugin\views\argument\BoundaryArgument;
use Drupal\geolocation_geometry\GeometryBoundaryTrait;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Argument handler for geolocation boundary.
 *
 * Argument format should be in the following format:
 * NE-Lat,NE-Lng,SW-Lat,SW-Lng, so "11.1,33.3,55.5,77.7".
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("geolocation_geometry_argument_boundary")
 */
class GeoBoundaryArgument extends BoundaryArgument {

  use GeometryBoundaryTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    if (!($this->query instanceof Sql)) {
      return;
    }

    if ($values = $this->getParsedBoundary()) {
      $placeholder = $this->placeholder() . '_boundary_geojson';

      $this->query->addWhereExpression(
        $group_by,
        self::getGeometryBoundaryQueryFragment($this->ensureMyTable(), $this->realField, $placeholder),
        self::getGeometryBoundaryQueryValue($placeholder, $values['lat_north_east'], $values['lng_north_east'], $values['lat_south_west'], $values['lng_south_west'])
      );
    }
  }

}
