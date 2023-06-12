<?php

namespace Drupal\flexfield\Plugin\FlexFieldType;

use Drupal\flexfield\Plugin\FlexFieldTypeBase;
use Drupal\flexfield\Plugin\Field\FieldType\FlexItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Base plugin class for numeric flex field types.
 */
class NumericBase extends FlexFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings() {
    return [
      'min' => '',
      'max' => '',
    ] + parent::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);

    // Number form element type
    $element['#type'] = 'number';

    // Add min/max if set
    if ($this->getWidgetSetting('min')) {
      $element['#min'] = $this->getWidgetSetting('min');
    }
    if ($this->getWidgetSetting('max')) {
      $element['#max'] = $this->getWidgetSetting('max');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::widgetSettingsForm($form, $form_state);

    $element['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $this->getWidgetSetting('min'),
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),'#prefix' => '<div class="flexfield-settings-inline">',
    ];

    $element['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $this->getWidgetSetting('max'),
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#suffix' => '</div>',
    ];

    return $element;
  }

}
