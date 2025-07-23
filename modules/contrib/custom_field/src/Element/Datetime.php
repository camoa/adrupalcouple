<?php

namespace Drupal\custom_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;

/**
 * Provides a custom_field_datetime element.
 */
#[FormElement('custom_field_datetime')]
class Datetime extends DatetimeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $info['#theme_wrappers'] = ['container', 'fieldset'];
    $info['#theme'] = NULL;
    $info['#attached'] = [
      'library' => [
        'custom_field/custom-field-datetime',
      ],
    ];

    return $info;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The processed element.
   */
  public static function processDatetime(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element['#attributes']['class'][] = 'custom-field-datetime-grid';
    return parent::processDatetime($element, $form_state, $complete_form);
  }

}
