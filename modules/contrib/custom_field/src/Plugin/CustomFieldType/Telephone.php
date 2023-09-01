<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Plugin implementation of the 'telephone' custom field type.
 *
 * Simple telephone custom field widget.
 *
 * @CustomFieldType(
 *   id = "telephone",
 *   label = @Translation("Telephone"),
 *   description = @Translation(""),
 *   category = @Translation("General"),
 *   data_types = {
 *     "telephone",
 *   }
 * )
 */
class Telephone extends CustomFieldTypeBase {

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
      'render' => 'value',
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
      '#type' => 'tel',
      '#maxlength' => $settings['maxlength'] ?? $this->maxLength,
      '#placeholder' => $settings['placeholder'] ?? NULL,
      '#size' => $settings['size'] ?? NULL,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widgetSettings['settings'] + self::defaultWidgetSettings()['settings'];
    $default_maxlength = $this->maxLength;
    if (is_numeric($settings['maxlength']) && $settings['maxlength'] < $this->maxLength) {
      $default_maxlength = $settings['maxlength'];
    }
    $element['settings']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $settings['size'],
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['settings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['settings']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Max length'),
      '#description' => $this->t('The maximum amount of characters in the field'),
      '#default_value' => $default_maxlength,
      '#min' => 1,
      '#max' => $this->maxLength,
    ];
    $element['settings']['maxlength_js'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show max length character count'),
      '#default_value' => $settings['maxlength_js'],
    ];

    $element['settings']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#default_value' => $settings['prefix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be prefixed to the value, like '$ ' or '&euro; '. Leave blank for none."),
    ];

    $element['settings']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#default_value' => $settings['suffix'],
      '#size' => 60,
      '#description' => $this->t("Define a string that should be suffixed to the value, like ' m', ' kb/s'. Leave blank for none."),
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
      '#title' => $this->t('Display prefix/suffix'),
      '#default_value' => $this->getFormatterSetting('prefix_suffix'),
    ];
    $form['render']['#options'] += ['link' => $this->t('Link')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): ?string {
    $settings = $this->getWidgetSetting('settings');
    $render = $this->getFormatterSetting('render');

    if ($render === 'hidden') {
      return NULL;
    }

    $output = parent::value($item);

    if ($output && $render === 'link') {
      // If the telephone number is 5 or less digits, parse_url() will think
      // it's a port number rather than a phone number which causes the link
      // formatter to throw an InvalidArgumentException. Avoid this by inserting
      // a dash (-) after the first digit - RFC 3966 defines the dash as a
      // visual separator character and so will be removed before the phone
      // number is used. See https://bugs.php.net/bug.php?id=70588 for more.
      // While the bug states this only applies to numbers <= 65535, a 5 digit
      // number greater than 65535 will cause parse_url() to return FALSE so
      // we need the work around on any 5 digit (or less) number.
      // First we strip whitespace so we're counting actual digits.
      $phone_number = preg_replace('/\s+/', '', $output);
      if (strlen($phone_number) <= 5) {
        $phone_number = substr_replace($phone_number, '-', 1, 0);
      }
      $build = [
        '#type' => 'link',
        '#title' => $phone_number,
        // Prepend 'tel:' to the telephone number.
        '#url' => Url::fromUri('tel:' . rawurlencode($phone_number)),
        '#options' => ['external' => TRUE],
      ];
      $output = \Drupal::service('renderer')->render($build);
    }
    // Account for prefix and suffix.
    if ($output && $this->getFormatterSetting('prefix_suffix')) {
      $prefix = $settings['prefix'] ?? '';
      $suffix = $settings['suffix'] ?? '';
      $output = $prefix . $output . $suffix;
    }

    return $output;
  }

}
