<?php

namespace Drupal\custom_field\Plugin;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeManagerInterface {

  /**
   * Get custom field plugin items from an array of custom field settings.
   *
   * @param array $settings
   *   The array of Drupal\custom_field\Plugin\Field\FieldType\CustomItem
   *   settings.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   The array of custom field plugin items to return.
   */
  public function getCustomFieldItems(array $settings): array;

  /**
   * Return the available widget plugins as an array keyed by plugin_id.
   *
   * @param string $type
   *   The column type to base options on.
   *
   * @return array
   *   The array of widget options.
   */
  public function getCustomFieldWidgetOptions(string $type): array;

  /**
   * Return the available formatter plugins as an array keyed by plugin_id.
   *
   * @param string $type
   *   The column type to base options on.
   *
   * @return array
   *   The array of formatter options.
   */
  public function getCustomFieldFormatterOptions(string $type): array;

  /**
   * An array of data types and properties keyed by type name.
   *
   * @return array[]
   *   Returns an array of data types.
   */
  public function dataTypes(): array;

  /**
   * Builds options for a select list based on dataTypes.
   *
   * @return array
   *   An array of options suitable for a select list.
   */
  public function dataTypeOptions(): array;

}
