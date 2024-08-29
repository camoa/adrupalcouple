<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'string_long' custom field type.
 *
 * @CustomFieldType(
 *   id = "string_long",
 *   label = @Translation("Text (long)"),
 *   description = @Translation("A field containing a long string value."),
 *   category = @Translation("Text"),
 *   default_widget = "textarea",
 *   default_formatter = "text_default",
 * )
 */
class StringLongType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'text',
      'size' => 'big',
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
