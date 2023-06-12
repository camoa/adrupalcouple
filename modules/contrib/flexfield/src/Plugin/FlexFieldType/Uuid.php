<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\flexfield\Plugin\FlexFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'uuid' flexfield type.
 *
 * Simple uuid flexfield widget. This doesn't actually render as a editable
 * widget on the form. Rather it sets a UUID on the field when the flexfield
 * is first created to give a unique identifier to the flexfield item.
 *
 * The main purpose of this field is to be able to identify a specific flexfield
 * item without having to rely on any of the exposed fields which could change
 * at any given time (i.e. content is updated, or delta is changed with a manual
 * reorder).
 *
 * @FlexFieldType(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   description = @Translation("")
 * )
 */
class Uuid extends FlexFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // We're not calling the parent widget method here since we don't want to
    // actually render this widget.
    $element = [
      '#type' => 'value',
      '#value' => !empty($items[$delta]->{$this->name}) ? $items[$delta]->{$this->name} : \Drupal::service('uuid')->generate(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    // Some table columns containing raw markup.
    $element['description'] = [
      '#markup' => '<em>This will set a UUID on the flexfield item the first time it is created and can be used as a unique identifier for the item in your custom code. This is the main use for this field type.</em>',
    ];

    return $element;
  }

}
