<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Plugin implementation of the 'text' customfield type.
 *
 * Simple textfield customfield widget. Value renders as it is entered by the
 * user.
 *
 * @CustomFieldType(
 *   id = "text",
 *   label = @Translation("Text"),
 *   description = @Translation(""),
 *   category = @Translation("Text"),
 *   data_types = {
 *     "string",
 *   }
 * )
 */
class Text extends CustomFieldTypeBase {

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
        'prefix' => '',
        'suffix' => '',
      ],
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings(): array {
    return [
      'prefix_suffix' => FALSE,
    ] + parent::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');
    // Add our widget type and additional properties and return.
    if (isset($settings['maxlength'])) {
      $element['#attributes']['data-maxlength'] = $settings['maxlength'];
    }
    if (isset($settings['maxlength_js']) && $settings['maxlength_js']) {
      $element['#maxlength_js'] = TRUE;
    }

    // Add prefix and suffix.
    if (isset($settings['prefix'])) {
      $element['#field_prefix'] = FieldFilteredMarkup::create($settings['prefix']);
    }
    if (isset($settings['suffix'])) {
      $element['#field_suffix'] = FieldFilteredMarkup::create($settings['suffix']);
    }

    return [
      '#type' => 'textfield',
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

    $element['settings']['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none."),
    ];

    $element['settings']['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::formatterSettingsForm($form, $form_state);

    $form['prefix_suffix'] = [
      '#type' => 'checkbox',
      '#title' => t('Display prefix and suffix'),
      '#default_value' => $this->getFormatterSetting('prefix_suffix'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): string {
    $settings = $this->getWidgetSetting('settings');
    $output = parent::value($item);

    // Account for prefix and suffix
    if ($this->getFormatterSetting('prefix_suffix')) {
      $prefix = $settings['prefix'] ?? '';
      $suffix = $settings['suffix'] ?? '';
      $output = $prefix . $output . $suffix;
    }

    return $output;
  }

}
