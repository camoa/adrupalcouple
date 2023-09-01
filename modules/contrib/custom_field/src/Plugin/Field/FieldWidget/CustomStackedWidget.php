<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'custom_stacked' widget.
 *
 * @FieldWidget(
 *   id = "custom_stacked",
 *   label = @Translation("Stacked"),
 *   weight = 2,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomStackedWidget extends CustomWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $element[$name] = $customItem->widget($items, $delta, $element, $form, $form_state);
    }

    return $element;
  }

}
