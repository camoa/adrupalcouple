<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'decimal' custom field type.
 *
 * @CustomFieldType(
 *   id = "decimal",
 *   label = @Translation("Number (decimal)"),
 *   description = @Translation("This field stores a number in the database in a fixed decimal format."),
 *   category = @Translation("Number"),
 *   default_widget = "decimal",
 *   default_formatter = "number_decimal"
 * )
 */
class DecimalType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'numeric',
      'precision' => $settings['precision'] ?? 10,
      'scale' => $settings['scale'] ?? 2,
      'unsigned' => $settings['unsigned'] ?? FALSE,
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

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = [];

    if (isset($settings['min']) && $settings['min'] !== '') {
      $min = $settings['min'];
      $constraints['Range']['min'] = $min;
      $constraints['Range']['minMessage'] = $this->t('%name: the value may be no less than %min.', [
        '%name' => $settings['name'],
        '%min' => $min,
      ]);
    }
    if (isset($settings['max']) && $settings['max'] !== '') {
      $max = $settings['max'];
      $constraints['Range']['max'] = $max;
      $constraints['Range']['maxMessage'] = $this->t('%name: the value may be no greater than %max.', [
        '%name' => $settings['name'],
        '%max' => $max,
      ]);
    }

    return $constraints;
  }

}
