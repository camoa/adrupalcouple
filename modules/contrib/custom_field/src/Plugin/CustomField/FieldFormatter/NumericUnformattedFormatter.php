<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'number_unformatted' formatter.
 *
 * @FieldFormatter(
 *   id = "number_unformatted",
 *   label = @Translation("Unformatted"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class NumericUnformattedFormatter extends DecimalFormatter {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $elements = parent::settingsForm($form, $form_state, $settings);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(array $settings) {

    return $settings['value'];
  }

}
