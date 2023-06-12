<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Base plugin class for numeric custom field types.
 */
abstract class NumericBase extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'min' => '',
        'max' => '',
        'prefix' => '',
        'suffix' => '',
        'placeholder' => '',
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
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');

    // Number form element type
    $element['#type'] = 'number';
    $element['#step'] = 'any';
    if (!empty($settings['placeholder'])) {
      $element['#placeholder'] = $settings['placeholder'];
    }

    // Add min/max if set
    if (isset($settings['min']) && is_numeric($settings['min'])) {
      $element['#min'] = $settings['min'];
    }
    if (isset($settings['max']) && is_numeric($settings['max'])) {
      $element['#max'] = $settings['max'];
    }

    // Add prefix and suffix.
    if (isset($settings['prefix'])) {
      $prefixes = explode('|', $settings['prefix']);
      $element['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if (isset($settings['suffix'])) {
      $suffixes = explode('|', $settings['suffix']);
      $element['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {

    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];

    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    $element['settings']['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $settings['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#prefix' => '<div class="customfield-settings-inline">',
    ];

    $element['settings']['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $settings['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#suffix' => '</div>',
    ];

    $element['settings']['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
    ];

    $element['settings']['suffix'] = [
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none. Separate singular and plural values with a pipe ('pound|pounds')."),
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
  public function value(CustomItem $item): ?string {
    $settings = $this->getWidgetSetting('settings');
    $value = parent::value($item);

    // Avoid doing unnecessary work on empty values.
    if (!isset($value) || $value === '') {
      return NULL;
    }

    $output = $this->numberFormat($value);

    // Account for prefix and suffix.
    if ($this->getFormatterSetting('prefix_suffix')) {
      $prefixes = isset($settings['prefix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['prefix'])) : [''];
      $suffixes = isset($settings['suffix']) ? array_map(['Drupal\Core\Field\FieldFilteredMarkup', 'create'], explode('|', $settings['suffix'])) : [''];
      $prefix = (count($prefixes) > 1) ? $this->formatPlural($value, $prefixes[0], $prefixes[1]) : $prefixes[0];
      $suffix = (count($suffixes) > 1) ? $this->formatPlural($value, $suffixes[0], $suffixes[1]) : $suffixes[0];
      $output = $prefix . $output . $suffix;
    }

    return $output;
  }

  /**
   * Formats a number.
   *
   * @param mixed $number
   *   The numeric value.
   *
   * @return string
   *   The formatted number.
   */
  abstract protected function numberFormat(mixed $number): string;

}
