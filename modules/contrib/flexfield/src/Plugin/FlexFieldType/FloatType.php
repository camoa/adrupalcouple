<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'float' flexfield type.
 *
 * Note the class name needs to be FloatType as "Float" is a reserved class
 * name in php.
 *
 * @FlexFieldType(
 *   id = "float",
 *   label = @Translation("Float"),
 *   description = @Translation("")
 * )
 */
class FloatType extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    // Add our widget type and additional properties and return.
    return [
      '#scale' => 'any',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::widgetSettingsForm($form, $form_state);

    $element['min']['#scale'] = 'any';
    $element['max']['#scale'] = 'any';

    return $element;
  }

}
