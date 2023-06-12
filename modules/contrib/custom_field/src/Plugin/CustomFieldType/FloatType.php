<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'float' customfield type.
 *
 * Note the class name needs to be FloatType as "Float" is a reserved class
 * name in php.
 *
 * @CustomFieldType(
 *   id = "float",
 *   label = @Translation("Float"),
 *   description = @Translation(""),
 *   category = @Translation("Number"),
 *   data_types = {
 *     "float",
 *   },
 * )
 */
class FloatType extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'scale' => 2,
        'decimal_separator' => '.',
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

    // Add our widget type and additional properties and return.
    return [
      '#step' => 'any',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];

    $element['settings']['min']['#scale'] = 'any';
    $element['settings']['max']['#scale'] = 'any';
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
    $element['settings']['decimal_separator'] = [
      '#type' => 'select',
      '#title' => t('Decimal marker'),
      '#options' => [
        '.' => t('Decimal point'),
        ',' => t('Comma'),
      ],
      '#default_value' => $settings['decimal_separator'],
    ];
    $element['settings']['scale'] = [
      '#type' => 'number',
      '#title' => t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $settings['scale'],
      '#description' => t('The number of digits to the right of the decimal.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number): string {
    $settings = $this->getWidgetSetting('settings');
    return number_format($number, $settings['scale'], $settings['decimal_separator'], $settings['thousand_separator']);
  }

}
