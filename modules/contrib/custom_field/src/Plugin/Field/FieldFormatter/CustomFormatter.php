<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_formatter' formatter.
 *
 * Generic formatter, renders the items using the custom_field theme hook
 * implementation.
 *
 * @FieldFormatter(
 *   id = "custom_formatter",
 *   label = @Translation("Customfield"),
 *   weight = 0,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomFormatter extends CustomFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'label_display' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {

    $form = parent::settingsForm($form, $form_state);

    $form['#attached']['library'][] = 'custom_field/customfield-inline';
    $form['label_display'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['customfield-inline'],
      ],
    ];

    $label_display = $this->getSetting('label_display');
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $form['label_display'][$name] = [
        '#type' => 'select',
        '#title' => $this->t('@label label', ['@label' => $customItem->getLabel()]),
        '#options' => $this->fieldLabelOptions(),
        '#default_value' => $label_display[$name] ?? 'above',
      ];
      $form['label_display'][$name]['#attributes']['class'][] = 'customfield-inline__field';
      $form['label_display'][$name]['#wrapper_attributes']['class'][] = 'customfield-inline__item';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $label_display = $this->getSetting('label_display');
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $summary[] = $this->t('@label label display: @label_display', [
        '@label' => $customItem->getLabel(),
        '@label_display' => isset($label_display[$name]) ? $this->fieldLabelOption($label_display[$name]) : 'above',
      ]);
    }

    return $summary;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item): array {
    $output = [
      '#theme' => [
        'customfield',
        'customfield__' . $this->fieldDefinition->get('field_name'),
      ],
      '#field_name' => $this->fieldDefinition->get('field_name'),
      '#items' => [],
    ];
    $label_display = $this->getSetting('label_display');

    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $markup = $customItem->value($item);
      if ($markup === '' || $markup === NULL) {
        continue;
      }
      $output['#items'][] = [
        'name' => $name,
        'value' => ['#markup' => $markup],
        'label' => $customItem->getLabel(),
        'label_display' => $label_display[$name] ?? 'above',
      ];
    }

    return $output;
  }

  /**
   * Returns an array of visibility options for customfield labels.
   *
   * Copied from Drupal\field_ui\Form\EntityViewDisplayEditForm (can't call
   * directly since it's protected)
   *
   * @return array
   *   An array of visibility options.
   */
  protected function fieldLabelOptions(): array {
    return [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually hidden') . ' -',
    ];
  }

  /**
   * Returns an individual option string for customfield labels.
   *
   * @return string
   *   The string value of a specified label option.
   */
  protected function fieldLabelOption($option): string {
    return $this->fieldLabelOptions()[$option];
  }

}
