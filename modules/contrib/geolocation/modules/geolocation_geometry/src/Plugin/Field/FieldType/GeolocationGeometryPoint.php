<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'geolocation' field type.
 *
 * @FieldType(
 *   id = "geolocation_geometry_point",
 *   label = @Translation("Geolocation Geometry - Point"),
 *   category = "spatial_fields",
 *   description = @Translation("This field stores spatial geometry data."),
 *   default_widget = "geolocation_geometry_geojson",
 *   default_formatter = "geolocation_geometry_data"
 * )
 */
class GeolocationGeometryPoint extends GeolocationGeometryBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['geometry']['pgsql_type'] = "geometry('POINT')";
    $schema['columns']['geometry']['mysql_type'] = 'point';

    return $schema;
  }

}
