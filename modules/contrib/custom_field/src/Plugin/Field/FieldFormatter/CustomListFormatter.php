<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the custom_list formatter.
 *
 * Renders the items as an item list.
 *
 * @FieldFormatter(
 *   id = "custom_list",
 *   label = @Translation("HTML list"),
 *   weight = 3,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomListFormatter extends BaseFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'list_type' => 'ul',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['list_type'] = [
      '#type' => 'select',
      '#title' => $this->t('List type'),
      '#options' => [
        'ul' => $this->t('Unordered list'),
        'ol' => $this->t('Numbered list'),
      ],
      '#default_value' => $this->getSetting('list_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $options = [
      'ul' => $this->t('Unordered list'),
      'ol' => $this->t('Numbered list'),
    ];
    $summary[] = $this->t('List type: @type', ['@type' => $options[$this->getSetting('list_type')]]);

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
    $field_name = $this->fieldDefinition->get('field_name');
    $class = Html::cleanCssIdentifier($field_name);
    $langcode = $item->getLangcode();
    $output = [
      '#theme' => [
        'item_list',
        'item_list__customfield',
        'item_list__' . $field_name,
      ],
      '#list_type' => $this->getSetting('list_type'),
      '#attributes' => [
        'class' => [$class, $class . '--list'],
      ],
    ];
    $values = $this->getFormattedValues($item, $langcode);

    foreach ($values as $value) {
      $output['#items'][] = [
        '#markup' => $value['label'] . ': ' . $value['value']['#markup'],
        '#wrapper_attributes' => [
          'class' => [$class . '__' . Html::cleanCssIdentifier($value['name'])],
        ],
      ];
    }

    return $output;
  }

}
