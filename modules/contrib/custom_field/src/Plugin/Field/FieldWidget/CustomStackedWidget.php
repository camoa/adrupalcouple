<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
    $field_settings = $this->getFieldSetting('field_settings');

    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $definition = $customItem->getPluginDefinition();
      $type = $field_settings[$name]['type'] ?? $definition['default_widget'];
      $widget_plugin = $this->customFieldWidgetManager->createInstance($type);
      if (!empty($widget_plugin)) {
        $element[$name] = $widget_plugin->widget($items, $delta, $element, $form, $form_state, $customItem);
      }
    }

    return $element;
  }

}
