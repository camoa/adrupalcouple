<?php

namespace Drupal\geolocation_geometry\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'geolocation' field type.
 *
 * @FieldType(
 *   id = "geolocation_geometry_multipoint",
 *   label = @Translation("Geolocation Geometry - MultiPoint"),
 *   category = "geo_spatial",
 *   description = @Translation("This field stores spatial geometry of type 'MultiPoint'."),
 *   default_widget = "geolocation_geometry_geojson",
 *   default_formatter = "geolocation_geometry_data"
 * )
 */
class GeolocationGeometryMultiPoint extends GeolocationGeometryBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition): array {
    $schema = parent::schema($field_definition);

    $schema['columns']['geometry']['pgsql_type'] = "geometry('MULTIPOINT')";
    $schema['columns']['geometry']['mysql_type'] = 'multipoint';

    return $schema;
  }

}
