<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'uuid' customfield type.
 *
 * Simple uuid customfield widget. This doesn't actually render as a editable
 * widget on the form. Rather it sets a UUID on the field when the customfield
 * is first created to give a unique identifier to the customfield item.
 *
 * The main purpose of this field is to be able to identify a specific customfield
 * item without having to rely on any of the exposed fields which could change
 * at any given time (i.e. content is updated, or delta is changed with a manual
 * reorder).
 *
 * @CustomFieldType(
 *   id = "uuid",
 *   label = @Translation("UUID"),
 *   description = @Translation(""),
 *   never_check_empty = TRUE,
 *   category = @Translation("General"),
 *   data_types = {
 *     "uuid",
 *   }
 * )
 */
class Uuid extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // We're not calling the parent widget method here since we don't want to
    // actually render this widget.
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    $element = [
      '#type' => 'value',
      '#value' => NULL,
    ];
    if (!$is_config_form) {
      $element['#value'] = !empty($items[$delta]->{$this->name}) ? $items[$delta]->{$this->name} : \Drupal::service('uuid')->generate();
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {

    $element = parent::widgetSettingsForm($form, $form_state);
    unset($element['settings']);
    unset($element['label']);

    // Some table columns containing raw markup.
    $element['description'] = [
      '#markup' => '<em>This will set a UUID on the custom field item the first time it is created and can be used as a unique identifier for the item in your custom code. This is the main use for this field type.</em>',
    ];

    return $element;
  }

}
