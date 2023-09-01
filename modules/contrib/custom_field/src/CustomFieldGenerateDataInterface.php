<?php

namespace Drupal\custom_field;

/**
 * Defines an interface for custom field data generation.
 */
interface CustomFieldGenerateDataInterface {

  /**
   * Generates field data for custom field.
   *
   * @param array $columns
   *   Array of field columns from the field storage settings.
   * @param array $field_settings
   *   Optional array of field widget settings.
   *
   * @return array
   *   Array of key/value pairs to populate custom field.
   */
  public function generateFieldData(array $columns, array $field_settings = []): array;

}
