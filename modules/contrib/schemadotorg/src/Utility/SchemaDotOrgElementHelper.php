<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Utility;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Helper class Schema.org element methods.
 */
class SchemaDotOrgElementHelper {

  /**
   * Set #parents property for child elements.
   *
   * @param array &$elements
   *   The elements.
   * @param array $parents
   *   The parents for the child elements.
   */
  public static function setElementParents(array &$elements, array $parents): void {
    foreach (Element::children($elements) as $key) {
      $elements[$key]['#parents'] = array_merge($parents, [$key]);
    }
  }

  /**
   * Form API callback. Remove unchecked options from value array.
   */
  public static function validateMultipleOptions(array &$element, FormStateInterface $form_state, array &$completed_form): void {
    $values = $element['#value'] ?: [];
    // Filter unchecked/unselected options whose value is 0.
    $values = array_filter($values, function ($value) {
      return $value !== 0;
    });
    $values = array_values($values);
    $form_state->setValueForElement($element, $values);
  }

}
