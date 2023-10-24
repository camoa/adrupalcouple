<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'datetime' custom field type.
 *
 * @CustomFieldType(
 *   id = "datetime",
 *   label = @Translation("Date"),
 *   description = @Translation("A field containing a Date."),
 *   category = @Translation("Date"),
 *   default_widget = "datetime_default",
 *   default_formatter = "datetime_default",
 * )
 */
class DateTimeType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => 20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): DataDefinition {
    return DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

}
