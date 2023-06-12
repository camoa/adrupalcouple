<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'decimal' flexfield type.
 *
 * @FlexFieldType(
 *   id = "decimal",
 *   label = @Translation("Decimal"),
 *   description = @Translation("")
 * )
 */
class Decimal extends NumericBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings() {
    return [
      'scale' => '',
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);

    // Add scale if set
    if ($this->getWidgetSetting('scale')) {
      $element['#scale'] = pow(0.1, $this->getWidgetSetting('scale'));
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::widgetSettingsForm($form, $form_state);

    $element['scale'] = [
      '#type' => 'number',
      '#title' => t('Scale', [], ['context' => 'decimal places']),
      '#min' => 0,
      '#max' => 10,
      '#default_value' => $this->getWidgetSetting('scale'),
      '#description' => t('The number of digits to the right of the decimal.'),
      '#suffix' => $element['max']['#suffix'],
    ];

    $element['min']['#scale'] = $this->getWidgetSetting('scale') ? pow(0.1, $this->getWidgetSetting('scale')) : 'any';
    $element['max']['#scale'] = $this->getWidgetSetting('scale') ? pow(0.1, $this->getWidgetSetting('scale')) : 'any';

    unset($element['max']['#suffix']);

    return $element;
  }

}
