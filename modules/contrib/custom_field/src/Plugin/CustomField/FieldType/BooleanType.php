<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'boolean' custom field type.
 *
 * @CustomFieldType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   description = @Translation("A field containing a boolean value."),
 *   never_check_empty = TRUE,
 *   category = @Translation("General"),
 *   default_widget = "checkbox",
 *   default_formatter = "boolean",
 * )
 */
class BooleanType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'int',
      'size' => 'tiny',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): DataDefinition {
    return DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

}
