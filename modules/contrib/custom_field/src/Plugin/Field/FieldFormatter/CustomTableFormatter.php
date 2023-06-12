<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'custom_table' formatter.
 *
 * Formats the custtomfield items as an html table.
 *
 * @FieldFormatter(
 *   id = "custom_table",
 *   label = @Translation("Table"),
 *   weight = 2,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomTableFormatter extends CustomFormatterBase {

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
  public function settingsSummary(): array {
    $summary[] = t('Custom field items will be rendered as a table.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    $component = Html::cleanCssIdentifier($this->fieldDefinition->get('field_name'));
    $customItems = $this->getCustomFieldItems();
    $header = [];
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customitem */
    foreach ($customItems as $customitem) {
      $header[] = $customitem->getLabel();
    }

    // @todo: Can this be deleted?
    $wrapper_id = 'customfield-settings-wrapper';

    // Jam the whole table in the first row since we're rendering the main field
    // items as table rows.
    $elements[0] = [
      '#theme' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => [$component]
      ],
      '#rows' => [],
    ];

    // Build the table rows and columns.
    foreach ($items as $delta => $item) {
      $elements[0]['#rows'][$delta]['class'][] = $component . '__item';
      foreach ($customItems as $name => $customitem) {
        $elements[0]['#rows'][$delta]['data'][$name] = [
          'data' => ['#markup' => $customitem->value($item)],
          'class' => [$component . '__' . Html::cleanCssIdentifier($name)],
        ];
      }
    }

    return $elements;
  }

}
