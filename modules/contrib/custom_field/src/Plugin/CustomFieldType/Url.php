<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text' customfield type.
 *
 *
 * @CustomFieldType(
 *   id = "url",
 *   label = @Translation("Url"),
 *   description = @Translation(""),
 *   category = @Translation("Url"),
 *   data_types = {
 *     "uri",
 *   }
 * )
 */
class Url extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'size' => 60,
        'placeholder' => '',
        'maxlength' => '',
        'maxlength_js' => FALSE,
      ],
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');
    // Add our widget type and additional properties and return.
    if (isset($settings['maxlength_js']) && $settings['maxlength_js']) {
      $element['#maxlength_js'] = TRUE;
    }
    return [
      '#type' => 'url',
      '#maxlength' => $settings['maxlength'] ?? $this->max_length,
      '#placeholder' => $settings['placeholder'] ?? NULL,
      '#size' => $settings['size'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];
    $default_maxlength = $this->max_length;
    if (is_numeric($settings['maxlength']) && $settings['maxlength'] < $this->max_length) {
      $default_maxlength = $settings['maxlength'];
    }
    $element['settings']['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['maxlength'] = [
      '#type' => 'number',
      '#title' => t('Max length'),
      '#description' => t('The maximum amount of characters in the field'),
      '#default_value' => $default_maxlength,
      '#min' => 1,
      '#max' => $this->max_length,
    ];
    $element['settings']['maxlength_js'] = [
      '#type' => 'checkbox',
      '#title' => t('Show max length character count'),
      '#default_value' => $settings['maxlength_js'],
    ];

    return $element;
  }

}
