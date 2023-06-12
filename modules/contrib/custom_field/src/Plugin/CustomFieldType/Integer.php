<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'integer' customfield type.
 *
 * The numeric base plugin has everything we need for an integer field but
 * we have this separate plugin definition class in case we want to do any
 * integer-specific stuff later.
 *
 * @CustomFieldType(
 *   id = "integer",
 *   label = @Translation("Integer"),
 *   description = @Translation(""),
 *   category = @Translation("Number"),
 *   data_types = {
 *     "integer",
 *   },
 * )
 */
class Integer extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'thousand_separator' => '',
      ]
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');

    $min_setting = $settings['min'] ?? NULL;
    // Make sure we force positive numbers when unsiqned.
    if ($this->unsigned && (!is_numeric($min_setting) || $min_setting < 0)) {
      $element['#min'] = 0;
    }

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {

    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];

    $options = [
      ''  => $this->t('- None -'),
      '.' => $this->t('Decimal point'),
      ',' => $this->t('Comma'),
      ' ' => $this->t('Space'),
      chr(8201) => $this->t('Thin space'),
      "'" => $this->t('Apostrophe'),
    ];
    $element['settings']['thousand_separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Thousand marker'),
      '#options' => $options,
      '#default_value' => $settings['thousand_separator'],
    ];
    // Prevent min negative numbers when storage is unsigned.
    if ($this->unsigned) {
      $element['settings']['min']['#min'] = 0;
      $element['settings']['min']['#default_value'] = $settings['min'];
      $element['settings']['min']['#description'] = t('The minimum value that should be allowed in this field.');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number): string {
    $settings = $this->getWidgetSetting('settings');
    return number_format($number, 0, NULL, $settings['thousand_separator']);
  }

}
