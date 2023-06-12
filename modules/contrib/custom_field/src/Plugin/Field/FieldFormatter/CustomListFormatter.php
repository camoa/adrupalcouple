<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_list formatter.
 *
 * Renders the customfield items as an item list
 *
 * @FieldFormatter(
 *   id = "custom_list",
 *   label = @Translation("HTML List"),
 *   weight = 3,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomListFormatter extends CustomFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'list_type' => 'ul'
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['list_type'] = [
      '#type' => 'select',
      '#title' => t('List Type'),
      '#options' => [
        'ul' => 'Unordered List',
        'ol' => 'Numbered List',
      ],
      '#default_value' => $this->getSetting('list_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $options = [
      'ul' => 'Un-ordered List',
      'ol' => 'Numbered List',
    ];
    $summary[] = t('List type: @type', ['@type' => $options[$this->getSetting('list_type')]]);

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
    $class = Html::cleanCssIdentifier($this->fieldDefinition->get('field_name'));
    $output = [
      '#theme' => [
        'item_list',
        'item_list__customfield',
        'item_list__' . $this->fieldDefinition->get('field_name'),
      ],
      '#list_type' => $this->getSetting('list_type'),
      '#attributes' => [
        'class' => [$class, $class . '--list']
      ],
    ];

    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customItem */
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $output['#items'][] = [
        '#markup' => $customItem->getLabel() . ': ' . $customItem->value($item),
        '#wrapper_attributes' => [
          'class' => [$class . '__' . Html::cleanCssIdentifier($name)],
        ]
      ];
    }

    return $output;
  }

}
