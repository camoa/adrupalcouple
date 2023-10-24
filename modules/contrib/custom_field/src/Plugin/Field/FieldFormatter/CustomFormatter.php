<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'custom_formatter' formatter.
 *
 * Generic formatter, renders the items using the custom_field theme hook
 * implementation.
 *
 * @FieldFormatter(
 *   id = "custom_formatter",
 *   label = @Translation("Default"),
 *   weight = 0,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomFormatter extends BaseFormatter {

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
    $field_name = $this->fieldDefinition->get('field_name');
    $langcode = $item->getLangcode();
    $output = [
      '#theme' => [
        'customfield',
        'customfield__' . $field_name,
      ],
      '#field_name' => $field_name,
      '#items' => [],
    ];

    $values = $this->getFormattedValues($item, $langcode);

    foreach ($values as $value) {
      $output['#items'][] = [
        'name' => $value['name'],
        'value' => [
          '#markup' => $value['value']['#markup'],
        ],
        'label' => $value['label'],
        'label_display' => $value['label_display'],
        'type' => $value['type'],
      ];
    }

    return $output;
  }

}
