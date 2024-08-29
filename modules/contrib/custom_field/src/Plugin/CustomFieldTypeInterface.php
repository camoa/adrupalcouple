<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for custom field Type plugins.
 */
interface CustomFieldTypeInterface extends PluginInspectionInterface {

  /**
   * Specifies whether the field supports only internal URLs.
   */
  const LINK_INTERNAL = 0x01;

  /**
   * Specifies whether the field supports only external URLs.
   */
  const LINK_EXTERNAL = 0x10;

  /**
   * Specifies whether the field supports both internal and external URLs.
   */
  const LINK_GENERIC = 0x11;

  /**
   * Value for the 'datetime_type' setting: store only a date.
   */
  const DATETIME_TYPE_DATE = 'date';

  /**
   * Value for the 'datetime_type' setting: store a date and time.
   */
  const DATETIME_TYPE_DATETIME = 'datetime';

  /**
   * Defines the timezone that dates should be stored in.
   */
  const STORAGE_TIMEZONE = 'UTC';

  /**
   * Defines the format that date and time should be stored in.
   */
  const DATETIME_STORAGE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * Defines the format that dates should be stored in.
   */
  const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * Defines the widget settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultWidgetSettings(): array;

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
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   A field.
   *
   * @return mixed
   *   The value.
   */
  public function value(FieldItemInterface $item): mixed;

  /**
   * The formatter plugin type.
   *
   * @return string
   *   The machine name of the formatter plugin.
   */
  public function getDefaultFormatter(): string;

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
   * The maxLength value for the custom field item.
   *
   * @return int
   *   The maxLength value.
   */
  public function getMaxLength(): int;

  /**
   * The dataType value for the custom field item.
   *
   * @return string
   *   The dataType value.
   */
  public function getDataType(): string;

  /**
   * The unsigned value from the custom field item.
   *
   * @return bool
   *   The boolean value for unsigned.
   */
  public function isUnsigned(): bool;

  /**
   * The unsigned value from the custom field item.
   *
   * @return int
   *   The scale value of the column.
   */
  public function getScale(): int;

  /**
   * The datetime_type value from the custom field item.
   *
   * @return string
   *   The datetime_type value of the column.
   */
  public function getDatetimeType(): string;

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
   * The widget settings for the custom field item.
   *
   * @return array
   *   An array of widget settings.
   */
  public function getWidgetSettings(): array;

  /**
   * Should the field item be included in the empty check?
   *
   * @return bool
   *   TRUE if the field item should be included, otherwise FALSE.
   */
  public function checkEmpty(): bool;

  /**
   * Returns an array of schema properties.
   *
   * @param array $settings
   *   Optional settings passed to the schema() function.
   *
   * @return array
   *   An array of schema properties for the field type.
   */
  public static function schema(array $settings): array;

  /**
   * Returns an array of property definitions.
   *
   * @param array $settings
   *   Optional settings passed to the propertyDefinitions() function.
   *
   * @return mixed
   *   The DataDefinition of properties for the field type.
   */
  public static function propertyDefinitions(array $settings): mixed;

  /**
   * Returns an array of constraints.
   *
   * @param array $settings
   *   An array of settings passed to the getConstraints() function.
   *
   * @return array
   *   Array of constraints.
   */
  public function getConstraints(array $settings): array;

  /**
   * Returns Url object for a field.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   A field.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  public function getUrl(FieldItemInterface $item);

  /**
   * Determines if a link is external.
   *
   * @return bool
   *   TRUE if the link is external, FALSE otherwise.
   */
  public function isExternal(FieldItemInterface $item);

}
