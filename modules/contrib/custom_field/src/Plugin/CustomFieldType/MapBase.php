<?php

namespace Drupal\custom_field\Plugin\CustomFieldType;

use Drupal\custom_field\Plugin\CustomFieldTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Base plugin class for map custom field types.
 */
class MapBase extends CustomFieldTypeBase {

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Get the base form element properties.
    $element = parent::widget($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'item';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {

    $element = parent::widgetSettingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item): ?string {
    // Value should be handled in a theme function to be useful.
    return NULL;
  }
}
