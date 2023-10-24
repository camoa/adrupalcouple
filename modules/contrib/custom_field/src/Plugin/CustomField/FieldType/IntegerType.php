<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'integer' custom field type.
 *
 * @CustomFieldType(
 *   id = "integer",
 *   label = @Translation("Number (integer)"),
 *   description = @Translation("This field stores a number in the database as an integer."),
 *   category = @Translation("Number"),
 *   default_widget = "integer",
 *   default_formatter = "number_integer",
 * )
 */
class IntegerType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'int',
      'size' => $settings['size'] ?? 'normal',
      'unsigned' => $settings['unsigned'] ?? FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): DataDefinition {
    return DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = [];
    // To prevent a PDO exception from occurring, restrict values in the range
    // allowed by databases.
    $min = $this->getDefaultMinValue($settings);
    $max = $this->getDefaultMaxValue($settings);
    $constraints['Range']['min'] = $min;
    $constraints['Range']['max'] = $max;

    return $constraints;
  }

  /**
   * Helper method to get the min value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The minimum value allowed by database.
   */
  protected function getDefaultMinValue(array $settings): int|float {
    if ($settings['unsigned']) {
      return 0;
    }

    // Each value is - (2 ^ (8 * bytes - 1)).
    $size_map = [
      'normal' => -2147483648,
      'tiny' => -128,
      'small' => -32768,
      'medium' => -8388608,
      'big' => -9223372036854775808,
    ];
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
  }

  /**
   * Helper method to get the max value restricted by databases.
   *
   * @param array $settings
   *   An array of field settings.
   *
   * @return int|float
   *   The maximum value allowed by database.
   */
  protected function getDefaultMaxValue(array $settings): int|float {
    if ($settings['unsigned']) {
      // Each value is (2 ^ (8 * bytes) - 1).
      $size_map = [
        'normal' => 4294967295,
        'tiny' => 255,
        'small' => 65535,
        'medium' => 16777215,
        'big' => 18446744073709551615,
      ];
    }
    else {
      // Each value is (2 ^ (8 * bytes - 1) - 1).
      $size_map = [
        'normal' => 2147483647,
        'tiny' => 127,
        'small' => 32767,
        'medium' => 8388607,
        'big' => 9223372036854775807,
      ];
    }
    $size = $settings['size'] ?? 'normal';

    return $size_map[$size];
  }

}
