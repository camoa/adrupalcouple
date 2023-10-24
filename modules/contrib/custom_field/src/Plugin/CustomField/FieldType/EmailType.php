<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;

/**
 * Plugin implementation of the 'email' custom field type.
 *
 * @CustomFieldType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("A field containing an e-mail value."),
 *   category = @Translation("General"),
 *   default_widget = "email",
 *   default_formatter = "email_mailto",
 * )
 */
class EmailType extends CustomFieldTypeBase {

  public const EMAIL_MAX_LENGTH = 254;

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => self::EMAIL_MAX_LENGTH,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): DataDefinition {
    return DataDefinition::create('email')
      ->setLabel(new TranslatableMarkup('%name value', ['%name' => $settings['name']]))
      ->setRequired(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    $constraints = [];
    $constraints['Length'] = [
      'max' => self::EMAIL_MAX_LENGTH,
      'maxMessage' => $this->t('%name: the email address can not be longer than @max characters.', [
        '%name' => $settings['name'],
        '@max' => self::EMAIL_MAX_LENGTH,
      ]),
    ];

    return $constraints;
  }

}
