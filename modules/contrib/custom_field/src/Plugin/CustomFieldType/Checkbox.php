<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'checkbox' custom field type.
 *
 * @CustomFieldType(
 *   id = "checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation(""),
 *   never_check_empty = TRUE,
 *   category = @Translation("General"),
 *   data_types = {
 *     "boolean",
 *   }
 * )
 */
class Checkbox extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings(): array {
    return [
      'value_checked' => 'Yes',
      'value_unchecked' => 'No',
    ] + parent::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array {

    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);

    // Add our widget type and additional properties and return.
    return [
      '#type' => 'checkbox',
    ] + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::formatterSettingsForm($form, $form_state);

    // Some table columns containing raw markup.
    $form['value_checked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checked value'),
      '#description' => $this->t('The value to display when this is checked.'),
      '#default_value' => $this->getFormatterSetting('value_checked'),
    ];

    // Some table columns containing raw markup.
    $form['value_unchecked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unchecked value'),
      '#description' => $this->t('The value to display when this is unchecked.'),
      '#default_value' => $this->getFormatterSetting('value_unchecked'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): ?string {
    $render = $this->getFormatterSetting('render');

    if ($render === 'hidden') {
      return NULL;
    }

    return $item->{$this->name} ? $this->getFormatterSetting('value_checked') : $this->getFormatterSetting('value_unchecked');
  }

}
