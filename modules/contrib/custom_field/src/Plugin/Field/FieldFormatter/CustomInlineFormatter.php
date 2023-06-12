<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_inline' formatter.
 *
 * Renders the customfield items inline using a simple separator and no additional
 * wrapper markup.
 *
 * @FieldFormatter(
 *   id = "custom_inline",
 *   label = @Translation("Inline"),
 *   weight = 1,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomInlineFormatter extends CustomFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'show_labels' => FALSE,
      'label_separator' => ': ',
      'item_separator' => ', ',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {

    $form = parent::settingsForm($form, $form_state);
    $id = 'customfield-show-labels';

    $form['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Labels?'),
      '#default_value' => $this->getSetting('show_labels'),
      '#attributes' => [
        'data-id' => $id,
      ],
    ];

    $form['label_separator'] = [
      '#type' => 'textfield',
      '#title' => t('Label Separator'),
      '#default_value' => $this->getSetting('label_separator'),
      '#states' => [
        'visible' => [
          ':input[data-id="' . $id . '"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $form['item_separator'] = [
      '#type' => 'textfield',
      '#title' => t('Label Separator'),
      '#default_value' => $this->getSetting('item_separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = t('Show labels: @show_labels', ['@show_labels' => $this->getSetting('label_display') ? 'Yes' : 'No']);
    if ($this->getSetting('label_display')) {
      $summary[] = t('Label Separator: @sep', ['@sep' => $this->getSetting('label_separator')]);
    }
    $summary[] = t('Item Separator: @sep', ['@sep' => $this->getSetting('item_separator')]);

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
    $output = [];

    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customitem */
    foreach ($this->getCustomFieldItems() as $name => $customitem) {
      if ($this->getSetting('show_labels')) {
        $output[] = implode($this->getSetting('label_separator'), [
          $customitem->getLabel(),
          $customitem->value($item),
        ]);
      }
      else {
        $output[] = $customitem->value($item);
      }
    }

    return ['#markup' => implode($this->getSetting('item_separator'), $output)];
  }

}
