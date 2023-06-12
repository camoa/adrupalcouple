<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\flexfield\Plugin\FlexFieldTypeBase;
use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'text' flexfield type.
 *
 * Simple textfield flexfield widget. Value renders as it is entered by the
 * user.
 *
 * @FlexFieldType(
 *   id = "checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation(""),
 *   never_check_empty = TRUE
 * )
 */
class Checkbox extends FlexFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings() {
    return [
      'value_checked' => t('Yes'),
      'value_unchecked' => t('No'),
    ] + parent::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

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
  public function formatterSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::formatterSettingsForm($form, $form_state);

    // Some table columns containing raw markup.
    $form['value_checked'] = [
      '#type' => 'textfield',
      '#title' => t('Checked Value'),
      '#description' => t('The value to display when this is checked.'),
      '#default_value' => $this->getFormatterSetting('value_checked'),
    ];

    // Some table columns containing raw markup.
    $form['value_unchecked'] = [
      '#type' => 'textfield',
      '#title' => t('Unchecked Value'),
      '#description' => t('The value to display when this is unchecked.'),
      '#default_value' => $this->getFormatterSetting('value_unchecked'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function value(FlexItem $item) {
    return $item->{$this->name} ? $this->getFormatterSetting('value_checked') : $this->getFormatterSetting('value_unchecked');
  }

}
