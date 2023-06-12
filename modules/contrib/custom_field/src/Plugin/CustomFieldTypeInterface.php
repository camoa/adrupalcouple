<?php

namespace Drupal\custom_field\Plugin;

use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeInterface extends PluginInspectionInterface {

  /**
   * Defines the widget settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultWidgetSettings(): array;

  /**
   * Defines the formatter settings for this plugin, if any.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultFormatterSettings(): array;

  /**
   * Returns a form for the widget settings for this custom field type.
   *
   * @param array $form
   *   The form where the settings form is being included in. Provided as a
   *   reference. Implementations of this method should return a new form
   *   element which will be inserted into the main settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the widget settings.
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Returns a form for the formatter settings for this custom field type.
   *
   * @param array $form
   *   The form where the settings form is being included in. Provided as a
   *   reference. Implementations of this method should return a new form
   *   element which will be inserted into the main settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   *
   * @return array
   *   The form definition for the formatter settings.
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Returns the Custom field item widget as form array.
   *
   * Called from the Custom field widget plugin formElement method.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   * @param int $delta
   * @param array $element
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   *
   * @see \Drupal\Core\Field\WidgetInterface::formElement() for parameter descriptions
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array;

  /**
   * Render the stored value of the custom field item.
   *
   * @param CustomItem $item
   *   A field.
   *
   * @return mixed
   *   The value.
   */
  public function value(CustomItem $item);

  /**
   * The label for the custom field item.
   *
   * @return string
   */
  public function getLabel(): string;

  /**
   * The machine name of the custom field item.
   *
   * @return string
   */
  public function getName(): string;

  /**
   * Gets a widget setting by name
   *
   * @param string $name
   *   The name of the widget setting to get.
   *
   * @return array
   */
  public function getWidgetSetting(string $name): array;

  /**
   * Gets a formatter setting by name
   *
   * @param string $name
   *   The name of the formatter setting to get.
   */
  public function getFormatterSetting(string $name);

  /**
   * The widget settings for the custom field item.
   *
   * @return array
   */
  public function getWidgetSettings(): array;

  /**
   * The formatter settings for the custom field item.
   *
   * @return array
   */
  public function getFormatterSettings(): array;

}
