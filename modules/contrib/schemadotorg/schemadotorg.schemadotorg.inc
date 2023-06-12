<?php

/**
 * @file
 * Schema.org hook implementations for contributed modules.
 */

declare(strict_types = 1);

use Drupal\Core\Field\FieldItemInterface;

/* ************************************************************************** */
// Duration field.
/* ************************************************************************** */

/**
 * Implements hook_schemadotorg_property_field_alter() for the duration_field.module.
 */
function duration_field_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings
): void {
  // Make sure the field type is set to 'duration'.
  if ($field_storage_values['type'] !== 'duration') {
    return;
  }

  // Set duration granularity to hours and minutes.
  $field_values['settings'] = ['granularity' => 'h:i'];
}

/**
 * Implements hook_schemadotorg_jsonld_schema_property_alter() for the duration_field.module.
 */
function duration_field_schemadotorg_jsonld_schema_property_alter(mixed &$value, FieldItemInterface $item): void {
  $field_type = $item->getFieldDefinition()->getType();
  if ($field_type !== 'duration') {
    return;
  }

  $value = $item->duration;
}

/* ************************************************************************** */
// Content browser.
/* ************************************************************************** */

/**
 * Implements hook_schemadotorg_property_field_alter() for the content_browser.module.
 */
function content_browser_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings
): void {
  // Make sure the field type is set to 'entity_reference'.
  if (!in_array($field_storage_values['type'], ['entity_reference', 'entity_reference_revisions'])) {
    return;
  }

  // Use the content browser for node entity references.
  $target_type = $field_storage_values['settings']['target_type'];
  if ($target_type === 'node') {
    $widget_id = 'entity_browser_entity_reference';
    $widget_settings = [
      'entity_browser' => 'browse_content',
      'field_widget_display' => 'label',
      'field_widget_edit' => TRUE,
      'field_widget_remove' => TRUE,
      'field_widget_replace' => TRUE,
      'open' => FALSE,
      'field_widget_display_settings' => [],
      'selection_mode' => 'selection_append',
    ];
  }
}

/* ************************************************************************** */
// Linkit.
/* ************************************************************************** */

/**
 * Implements hook_schemadotorg_property_field_alter() for the linkit.module.
 */
function linkit_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings
): void {
  // Make sure the field type is set to 'link'.
  if ($field_storage_values['type'] !== 'link') {
    return;
  }

  // Use linkit for the link URL autocompletion.
  $widget_id = 'linkit';
  $widget_settings = ['linkit_auto_link_text' => TRUE];
}
