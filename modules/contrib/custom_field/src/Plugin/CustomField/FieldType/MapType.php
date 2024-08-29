<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'Map' custom field type.
 *
 * @CustomFieldType(
 *   id = "map",
 *   label = @Translation("Map"),
 *   description = @Translation("A field for storing a serialized array of values."),
 *   category = @Translation("General"),
 *   default_widget = "map_key_value",
 *   default_formatter = "string",
 * )
 */
class MapType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'blob',
      'size' => 'big',
      'serialize' => TRUE,
      'description' => 'A serialized array of values.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): MapDataDefinition {
    return MapDataDefinition::create('map')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

}
