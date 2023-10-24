<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'string' custom field type.
 *
 * @CustomFieldType(
 *   id = "string",
 *   label = @Translation("Text (plain)"),
 *   description = @Translation("A field containing a plain string value."),
 *   category = @Translation("Text"),
 *   default_widget = "text",
 *   default_formatter = "string",
 * )
 */
class StringType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => $settings['max_length'] ?? 255,
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
    if ($max_length = $settings['max_length']) {
      $constraints['Length'] = [
        'max' => $max_length,
        'maxMessage' => $this->t('%name: may not be longer than @max characters.', [
          '%name' => $settings['name'],
          '@max' => $max_length,
        ]),
      ];
    }

    return $constraints;
  }

}
