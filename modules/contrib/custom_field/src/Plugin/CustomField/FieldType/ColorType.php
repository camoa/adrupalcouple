<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'color' custom field type.
 *
 * @CustomFieldType(
 *   id = "color",
 *   label = @Translation("Color"),
 *   description = @Translation("A field containing a hexadecimal color value."),
 *   category = @Translation("General"),
 *   default_widget = "color",
 *   default_formatter = "string",
 * )
 */
class ColorType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => 7,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): DataDefinition {
    return DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

}
