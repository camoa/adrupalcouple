<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'radios' flexfield type.
 *
 * @FlexFieldType(
 *   id = "radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("")
 * )
 */
class Radios extends Select {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    // Add our widget type and additional properties and return.
    return [
      '#type' => 'radios',
    ] + $element;
  }

}
