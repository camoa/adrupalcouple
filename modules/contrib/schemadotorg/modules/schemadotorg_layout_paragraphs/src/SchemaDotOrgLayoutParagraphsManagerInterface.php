<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_layout_paragraphs;

use Drupal\Core\Form\FormStateInterface;

/**
 * Schema.org layout paragraphs manager.
 */
interface SchemaDotOrgLayoutParagraphsManagerInterface {

  /**
   * Alter Schema.org mapping defaults to support layout paragraphs.
   *
   * @param array &$defaults
   *   The Schema.org mapping entity default values.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   */
  public function alterMappingDefaults(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void;

  /**
   * Alter the Schema.org UI mapping form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterMappingForm(array &$form, FormStateInterface &$form_state): void;

  /**
   * Alter field storage and field values before they are created.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param array $field_storage_values
   *   Field storage config values.
   * @param array $field_values
   *   Field config values.
   * @param string|null $widget_id
   *   The plugin ID of the widget.
   * @param array $widget_settings
   *   An array of widget settings.
   * @param string|null $formatter_id
   *   The plugin ID of the formatter.
   * @param array $formatter_settings
   *   An array of formatter settings.
   *
   * @see schemadotorg_paragraphs_schemadotorg_property_field_alter()
   */
  public function alterPropertyField(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings
  ): void;

  /**
   * Get layout paragraphs Schema.org property name.
   *
   * @return string
   *   The layout paragraphs Schema.org property name.
   */
  public function getPropertyName(): string;

  /**
   * Get layout paragraphs field machine name.
   *
   * @return string
   *   The layout paragraphs field machine name.
   */
  public function getMachineName(): string;

  /**
   * Get layout paragraphs field name.
   *
   * @return string
   *   The layout paragraphs field name.
   */
  public function getFieldName(): string;

}
