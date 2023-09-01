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
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   A form element array containing basic properties for the widget:
   *   - #field_parents: The 'parents' space for the field in the form. Most
   *       widgets can simply overlook this property. This identifies the
   *       location where the field values are placed within
   *       $form_state->getValues(), and is used to access processing
   *       information for the field through the getWidgetState() and
   *       setWidgetState() methods.
   *   - #title: The sanitized element label for the field, ready for output.
   *   - #description: The sanitized element description for the field, ready
   *     for output.
   *   - #required: A Boolean indicating whether the element value is required;
   *     for required multiple value fields, only the first widget's values are
   *     required.
   *   - #delta: The order of this item in the array of sub-elements; see $delta
   *     above.
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for a single widget for this field.
   *
   * @see \Drupal\Core\Field\WidgetInterface::formElement()
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array;

  /**
   * Render the stored value of the custom field item.
   *
   * @param \Drupal\custom_field\Plugin\Field\FieldType\CustomItem $item
   *   A field.
   *
   * @return mixed
   *   The value.
   */
  public function value(CustomItem $item): mixed;

  /**
   * The label for the custom field item.
   *
   * @return string
   *   The label.
   */
  public function getLabel(): string;

  /**
   * The machine name of the custom field item.
   *
   * @return string
   *   The machine name.
   */
  public function getName(): string;

  /**
   * Gets a widget setting by name.
   *
   * @param string $name
   *   The name of the widget setting to get.
   *
   * @return array
   *   An array of properties.
   */
  public function getWidgetSetting(string $name): array;

  /**
   * Gets a formatter setting by name.
   *
   * @param string $name
   *   The name of the formatter setting to get.
   */
  public function getFormatterSetting(string $name);

  /**
   * The widget settings for the custom field item.
   *
   * @return array
   *   An array of widget settings.
   */
  public function getWidgetSettings(): array;

  /**
   * The formatter settings for the custom field item.
   *
   * @return array
   *   An array of formatter settings.
   */
  public function getFormatterSettings(): array;

  /**
   * Should the field item be included in the empty check?
   *
   * @return bool
   *   TRUE if the field item should be included, otherwise FALSE.
   */
  public function checkEmpty(): bool;

}
