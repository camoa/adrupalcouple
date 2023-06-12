<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'select' customfield type.
 *
 * @CustomFieldType(
 *   id = "select",
 *   label = @Translation("Select list"),
 *   description = @Translation(""),
 *   category = @Translation("Lists"),
 *   data_types = {
 *     "string",
 *     "integer",
 *     "float",
 *   },
 * )
 */
class Select extends ListBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->widget_settings['settings'];

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'select',
      '#empty_option' => $settings['empty_option'],
    ] + $element;
  }

}
