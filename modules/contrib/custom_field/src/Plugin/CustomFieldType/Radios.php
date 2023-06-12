<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'radios' customfield type.
 *
 * @CustomFieldType(
 *   id = "radios",
 *   label = @Translation("Radios"),
 *   description = @Translation(""),
 *   category = @Translation("Lists"),
 *   data_types = {
 *     "string",
 *     "integer",
 *     "float",
 *   },
 * )
 */
class Radios extends ListBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->widget_settings['settings'];
    // Add our widget type and additional properties and return.
    $element['#type'] = 'radios';
    if (!$settings['required']) {
      $options = $element['#options'];
      $options = ['' => $settings['empty_option']] + $options;
      $element['#options'] = $options;
    }

    return $element;
  }

}
