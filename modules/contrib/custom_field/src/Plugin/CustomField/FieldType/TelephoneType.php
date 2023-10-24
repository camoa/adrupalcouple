<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'telephone' custom field type.
 *
 * @CustomFieldType(
 *   id = "telephone",
 *   label = @Translation("Telephone number"),
 *   description = @Translation("This field stores a telephone number in the database."),
 *   category = @Translation("General"),
 *   default_widget = "telephone",
 *   default_formatter = "telephone_link",
 * )
 */
class TelephoneType extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => 255,
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
