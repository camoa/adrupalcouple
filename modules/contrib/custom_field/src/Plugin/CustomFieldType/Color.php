<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'color' custom field type.
 *
 * Simple color custom field widget.
 *
 * @CustomFieldType(
 *   id = "color",
 *   label = @Translation("Color"),
 *   description = @Translation(""),
 *   category = @Translation("General"),
 *   data_types = {
 *     "color",
 *   }
 * )
 */
class Color extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'color',
      '#maxlength' => 7,
      '#size' => 7,
    ] + $element;
  }

}
