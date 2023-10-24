<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;

/**
 * Plugin implementation of the 'hidden' formatter.
 *
 * @FieldFormatter(
 *   id = "hidden",
 *   label = @Translation("Hidden"),
 *   field_types = {
 *     "boolean",
 *     "string",
 *     "string_long",
 *     "uri",
 *     "email",
 *     "map",
 *     "telephone",
 *     "uuid",
 *     "color",
 *     "integer",
 *     "float",
 *     "datetime",
 *   }
 * )
 */
class HiddenFormatter implements CustomFieldFormatterInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formatValue(array $settings) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

}
