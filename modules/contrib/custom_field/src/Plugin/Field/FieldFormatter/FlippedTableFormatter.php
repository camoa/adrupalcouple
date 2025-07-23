<?php

declare(strict_types=1);

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'flipped_table' formatter.
 */
#[FieldFormatter(
  id: 'flipped_table',
  label: new TranslatableMarkup('Table (flipped)'),
  description: new TranslatableMarkup('Formats the custom field items as html table with first column header.'),
  field_types: [
    'custom',
  ],
  weight: 2,
)]
class FlippedTableFormatter extends BaseFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    foreach ($this->getCustomFieldItems() as $name => $custom_item) {
      // Remove non-applicable settings.
      $label_options = $form['fields'][$name]['content']['formatter_settings']['label_display']['#options'];
      unset($label_options['inline']);
      $label_options['above'] = $this->t('Default');
      $form['fields'][$name]['content']['formatter_settings']['label_display']['#options'] = $label_options;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    if (!$items->isEmpty()) {
      $component = Html::cleanCssIdentifier($this->fieldDefinition->getName());
      $settings = $this->getSetting('fields') ?? [];
      $custom_items = $this->sortFields($settings);

      // Initialize the table rows array.
      $rows = [];

      // Iterate over each sub-field (Name, Price, SKU, Image).
      foreach ($custom_items as $name => $custom_item) {
        $setting = $settings[$name] ?? [];
        $is_hidden = isset($setting['format_type']) && $setting['format_type'] === 'hidden';
        if ($is_hidden) {
          continue;
        }

        // Set the field label for the first column.
        $formatter_settings = $setting['formatter_settings'] ?? [];
        $field_label = $formatter_settings['field_label'] ?? NULL;
        $label = !empty($field_label) ? $field_label : $custom_item->getLabel();
        $label_display = $formatter_settings['label_display'] ?? '';

        // Prepare the label render array to prevent HTML escaping.
        $label_output = [
          '#markup' => $label,
        ];

        if ($label_display === 'hidden') {
          $label_output = '';
        }
        elseif ($label_display === 'visually_hidden') {
          $label_output = [
            '#markup' => '<span class="visually-hidden">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>',
          ];
        }

        // Initialize row data with the label as the first column.
        $row = [
          'data' => [
            [
              'data' => $label_output,
              'header' => TRUE,
              'scope' => 'row',
            ],
          ],
          'class' => [$component . '__' . Html::cleanCssIdentifier((string) $name)],
        ];

        // Add a column for each field item (delta).
        foreach ($items as $item) {
          $values = $this->getFormattedValues($item, $langcode);
          $value = $values[$name] ?? NULL;
          $output = NULL;

          if ($value !== NULL) {
            $output = [
              '#theme' => 'custom_field_item',
              '#field_name' => $name,
              '#name' => $value['name'],
              '#value' => $value['value']['#markup'],
              '#label' => $value['label'],
              '#label_display' => 'hidden',
              '#type' => $value['type'],
              '#wrappers' => $value['wrappers'],
              '#entity_type' => $value['entity_type'],
              '#lang_code' => $langcode,
            ];
          }

          $row['data'][] = [
            'data' => $output,
          ];
        }

        $rows[] = $row;
      }

      // Define the table element without a header.
      $elements[0] = [
        '#theme' => 'table',
        '#attributes' => [
          'class' => [$component],
        ],
        '#rows' => $rows,
      ];
    }

    return $elements;
  }

}
