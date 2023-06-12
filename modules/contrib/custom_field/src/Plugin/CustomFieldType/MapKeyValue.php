<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Map (Key Value)' customfield type.
 *
 * @CustomFieldType(
 *   id = "map_key_value",
 *   label = @Translation("Map (Key Value)"),
 *   description = @Translation(""),
 *   category = @Translation("General"),
 *   data_types = {
 *     "map",
 *   },
 * )
 */
class MapKeyValue extends MapBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $element['#element_validate'] = [[static::class, 'validateArrayValues']];
    $field_name = $items->getFieldDefinition()->getName();
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    $map_list = $element['#default_value'];

    if ($is_config_form) {
      $map_values = $form_state->getValue(['default_value_input', $field_name, $delta, $this->name]);
    }
    else {
      $map_values = $form_state->getValue([$field_name, $delta, $this->name]);
    }

    if (!empty($map_values) && !isset($map_values['data'])) {
      $map_list = $map_values;
    }

    $options_wrapper_id = $field_name . $delta . $this->name;
    $element['#prefix'] = '<div class="form-type--map" id="' . $options_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    if ($form_state->isRebuilding()) {
      $trigger = $form_state->getTriggeringElement();
      if ($trigger['#name'] == 'add_item:' . $this->name) {
        $map_list[] = ['key'=> '', 'value' => ''];
        $form_state->set('add', NULL);
      }
      if ($form_state->get('remove')) {
        $remove = $form_state->get('remove');
        if ($remove['name'] == 'remove:' . $options_wrapper_id . $trigger['#delta']) {
          unset($map_list[$remove['key']]);
          $form_state->set('remove', NULL);
        }
      }
    }
    $element['data'] = [
      '#type' => 'table',
      '#header' => [
        t('Key'),
        t('Value'),
        '',
      ],
    ];
    if (!empty($map_list)) {
      foreach ($map_list as $key => $value) {
        $element['data'][$key]['key'] = [
          '#type' => 'textfield',
          '#title' => t('Key'),
          '#title_display' => 'invisible',
          '#default_value' => $value['key'] ?? '',
          '#required' => TRUE,
        ];
        $element['data'][$key]['value'] = [
          '#type' => 'textfield',
          '#title' => t('Value'),
          '#title_display' => 'invisible',
          '#default_value' => $value['value'] ?? '',
          '#required' => TRUE,
        ];
        $element['data'][$key]['remove'] = [
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#submit' => [get_class($this) . '::removeItem'],
          '#name' => 'remove:' . $options_wrapper_id . $key,
          '#delta' => $key,
          '#ajax' => [
            'callback' => [$this, 'actionCallback'],
            'wrapper' => $options_wrapper_id,
          ],
          '#limit_validation_errors' => [[$is_config_form ? 'default_value_input' : $field_name]],
        ];
      }
    }
    $element['add_item'] = [
      '#type' => 'submit',
      '#value' => t('Add item'),
      '#submit' => [get_class($this) . '::addItem'],
      '#name' => 'add_item:' . $this->name,
      '#ajax' => [
        'callback' => [$this, 'actionCallback'],
        'wrapper' => $options_wrapper_id,
      ],
      '#limit_validation_errors' => [[$is_config_form ? 'default_value_input' : $field_name]],
    ];
    return $element;

  }

  /**
   * The #element_validate callback for map field array values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateArrayValues($element, FormStateInterface $form_state) {
    $values = isset($element['data']) ? $element['data']['#value'] : NULL;
    $is_config_form = $form_state->getBuildInfo()['base_form_id'] == 'field_config_form';
    if (is_array($values)) {
      $unique_keys = [];
      foreach ($values as $value) {
        // Make sure each key is unique.
        if (in_array($value['key'], $unique_keys)) {
          $form_state->setError($element, t('All keys must be unique.'));
          break;
        }
        else {
          $unique_keys[] = $value['key'];
        }
      }
      $form_state->setValueForElement($element, $values);
    }
    elseif ($is_config_form) {
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * Submit handler for the "add item" button.
   *
   */
  public static function addItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('add', $form_state->getTriggeringElement()['#name']);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove item" button.
   *
   */
  public static function removeItem(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $form_state->set('remove', ['name' => $trigger['#name'], 'key' => $trigger['#delta']]);
    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    return $form[$parents[0]][$parents[1]][$parents[2]][$parents[3]];
  }

}
