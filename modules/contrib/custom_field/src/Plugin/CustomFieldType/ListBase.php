<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Base plugin class for list custom field types.
 */
class ListBase extends CustomFieldTypeBase {

  /**
   * The data type of this field in the table.
   *
   * @var string
   */
  protected static $storage_type = '';

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'settings' => [
        'allowed_values' => [],
        'empty_option' => '- Select -',
      ],
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings(): array {
    return [
      'render' => 'value',
    ] + parent::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);
    $settings = $this->getWidgetSetting('settings');

    $options = [];
    if (!empty($settings['allowed_values'])) {
      foreach ($settings['allowed_values'] as $option) {
        $options[$option['key']] = $option['value'];
      }
    }

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'select',
      '#options' => $options,
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {

    $element = parent::widgetSettingsForm($form, $form_state);
    $settings = $this->widget_settings['settings'] + self::defaultWidgetSettings()['settings'];

    static::$storage_type = $this->data_type;

    $values = $form_state->getValues();
    if (empty($values)) {
      $allowed_values = $settings['allowed_values'] ?: [];
    }
    else {
      $value_settings = $values['settings']['field_settings'][$this->name]['widget_settings']['settings'];
      $allowed_values = $value_settings['allowed_values'] ?? [];
    }

    if ($form_state->isRebuilding()) {
      $trigger = $form_state->getTriggeringElement();
      if ($trigger['#name'] == 'add_row:' . $this->name) {
        $allowed_values = is_array($allowed_values) ? $allowed_values : [];
        $allowed_values[] = [
          'key' => NULL,
          'value' => '',
        ];
        $form_state->set('add', NULL);
      }
      if ($form_state->get('remove')) {
        $remove = $form_state->get('remove');
        if ($remove['name'] === $this->name) {
          unset($allowed_values[$remove['key']]);
          $form_state->set('remove', NULL);
        }
      }
    }

    $options_wrapper_id = 'options-wrapper-' . $this->name;
    $element['#prefix'] = '<div id="' . $options_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['settings']['empty_option'] = [
      '#type' => 'textfield',
      '#title' => t('Empty Option'),
      '#description' => t('Option to show when field is not required.'),
      '#default_value' => $settings['empty_option'],
      '#required' => TRUE,
    ];

    $element['settings']['allowed_values'] = [
      '#type' => 'table',
      '#caption' => t('<strong>Options</strong>'),
      '#header' => [
        t('Key'),
        t('Value'),
        '',
      ],
      '#element_validate' => [[static::class, 'validateAllowedValues']],
    ];
    if (!empty($allowed_values)) {
      $allowed_values_count = count($allowed_values);
      foreach ($allowed_values as $key => $value) {
        $key_properties = [
          '#title' => t('Key'),
          '#title_display' => 'invisible',
          '#default_value' => $value['key'],
          '#required' => TRUE,
        ];
        // Change the field type based on how data is stored.
        switch ($this->data_type) {
          case 'integer':
            if ($this->unsigned) {
              $key_properties['#min'] = 0;
            }
            $element['settings']['allowed_values'][$key]['key'] = [
                '#type' => 'number',
              ] + $key_properties;
            break;
          case 'float':
            $element['settings']['allowed_values'][$key]['key'] = [
                '#type' => 'number',
                '#step' => 'any',
              ] + $key_properties;
            break;
          default:
            $element['settings']['allowed_values'][$key]['key'] = [
                '#type' => 'textfield',
              ] + $key_properties;
        }
        $element['settings']['allowed_values'][$key]['value'] = [
          '#type' => 'textfield',
          '#title' => t('Value'),
          '#title_display' => 'invisible',
          '#default_value' => $value['value'],
          '#required' => TRUE,
        ];
        $element['settings']['allowed_values'][$key]['remove'] = [
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#submit' => [get_class($this) . '::removeSubmit'],
          '#name' => 'remove:' . $this->name . '_' . $key,
          '#delta' => $key,
          '#disabled' => $allowed_values_count <= 1,
          '#ajax' => [
            'callback' => [$this, 'actionCallback'],
            'wrapper' => $options_wrapper_id,
          ],
        ];
      }
    }
    $element['settings']['add_row'] = [
      '#type' => 'submit',
      '#value' => t('Add option'),
      '#submit' => [get_class($this) . '::addSubmit'],
      '#name' => 'add_row:' . $this->name,
      '#ajax' => [
        'callback' => [$this, 'actionCallback'],
        'wrapper' => $options_wrapper_id,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::formatterSettingsForm($form, $form_state);

    $form['render'] = [
      '#type' => 'select',
      '#title' => t('Output'),
      '#options' => [
        'value' => t('Value'),
        'key' => t('Key'),
      ],
      '#default_value' => $this->getFormatterSetting('render'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): string {
    $settings = $this->getWidgetSetting('settings');

    if ($this->getFormatterSetting('render') == 'key') {
      return parent::value($item);
    }
    else {
      $allowed_values = $settings['allowed_values'];
      if (empty($allowed_values)) {
        return parent::value($item);
      }
      $item_value = '';
      foreach ($allowed_values as $value) {
        if ($value['key'] === $item->{$this->name}) {
          $item_value = $value['value'];
          break;
        }
      }
      return $item_value;
    }
  }

  /**
   * The #element_validate callback for select field allowed values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = $element['#value'];

    if (is_array($values)) {
      // Check that keys are valid for the field type.
      $unique_keys = [];
      foreach ($values as $key => $value) {
        // Make sure each key is unique.
        if (!in_array($value['key'], $unique_keys)) {
          $unique_keys[] = $value['key'];
        }
        else {
          $form_state->setError($element, t('Allowed value key must be unique.'));
          break;
        }

        switch (static::$storage_type) {
          case 'integer':
          case 'float':
            if (!is_numeric($value['key'])) {
              $form_state->setError($element, t('Allowed value key must be numeric.'));
              break;
            }
            break;
        }
      }
      $form_state->setValueForElement($element, $values);
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];

    return $form[$parents[0]][$parents[1]][$parents[2]][$parents[3]];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('add', $form_state->getTriggeringElement()['#name']);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#parents'];
    $form_state->set('remove', ['name' => $parents[2], 'key' => $trigger['#delta']]);
    $form_state->setRebuild();
  }

}
