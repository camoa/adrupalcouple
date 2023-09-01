<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Plugin implementation of the 'email' custom field type.
 *
 * Simple email custom field widget. Value renders as it is entered by the
 * user.
 *
 * @CustomFieldType(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation(""),
 *   category = @Translation("General"),
 *   data_types = {
 *     "string",
 *     "email",
 *   }
 * )
 */
class Email extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'size' => 60,
        'placeholder' => '',
      ],
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'email',
      '#maxlength' => $this->maxLength,
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

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::formatterSettingsForm($form, $form_state);

    $form['render']['#options'] += ['link' => $this->t('Link')];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): mixed {
    $render = $this->getFormatterSetting('render');

    if ($render === 'hidden') {
      return NULL;
    }

    $output = parent::value($item);

    if ($output && $render === 'link') {
      $build = [
        '#type' => 'link',
        '#title' => $output,
        '#url' => Url::fromUri('mailto:' . $output),
      ];
      $output = \Drupal::service('renderer')->render($build);
    }

    return $output;
  }

}
