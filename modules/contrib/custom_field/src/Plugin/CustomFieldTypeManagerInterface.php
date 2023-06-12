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
   * Return the available widgets labels as an array keyed by plugin_id.
   *
   * @param string $type
   *   The column type to base options on.
   *
   * @return array
   *   The array of widget labels.
   */
  public function getCustomFieldWidgetOptions(string $type): array;

}
