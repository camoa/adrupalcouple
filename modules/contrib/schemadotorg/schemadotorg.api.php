<?php

/**
 * @file
 * Hooks to define and alter mappings, entity types and fields.
 */

declare(strict_types = 1);

// phpcs:disable DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the field types for Schema.org property.
 *
 * @param array $field_types
 *   An array of field types.
 * @param string $schema_type
 *   The Schema.org type.
 * @param string $schema_property
 *   The Schema.org property.
 */
function hook_schemadotorg_property_field_type_alter(array &$field_types, string $schema_type, string $schema_property): void {
  // Use SmartDate for startDate and endDate.
  if (in_array($schema_property, ['startDate', 'endData'])
    || \Drupal::moduleHandler()->moduleExists('smartdate')) {
    $field_types = ['smartdate' => 'smartdate'] + $field_types;
  }
}

/**
 * Prepare a property's field data before the Schema.org mapping form.
 *
 * @param array &$default_field
 *   The default values used in the Schema.org mapping form.
 * @param string $schema_type
 *   The Schema.org type.
 * @param string $schema_property
 *   The Schema.org property.
 */
function hook_schemadotorg_property_field_prepare(array &$default_field, string $schema_type, string $schema_property): void {
  // Programmatically update the name field for an Event Schema.org type.
  if ($schema_type === 'Event' && $schema_property === 'name') {
    $default_field['name']['label'] = t('Event title');
  }
}

/**
 * Alter bundle entity type before it is created.
 *
 * @param array &$values
 *   The bundle entity type values.
 * @param string $schema_type
 *   The Schema.org type.
 * @param string $entity_type_id
 *   The entity type ID.
 */
function hook_schemadotorg_bundle_entity_alter(array &$values, string $schema_type, string $entity_type_id): void {
  $entity_values =& $values['entity'];

  // Remove the description from the bundle entity before it is created.
  // @see schemadotorg_descriptions_schemadotorg_bundle_entity_alter()
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
  $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder */
  $schema_type_builder = \Drupal::service('schemadotorg.schema_type_builder');

  $definition = $schema_type_manager->getType($schema_type);
  $description = $schema_type_builder->formatComment($definition['comment'], ['base_path' => 'https://schema.org/']);
  if ($entity_values['description'] === $description) {
    $entity_values['description'] = '';
  }
}

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
 */
function hook_schemadotorg_property_field_alter(
  string $schema_type,
  string $schema_property,
  array &$field_storage_values,
  array &$field_values,
  ?string &$widget_id,
  array &$widget_settings,
  ?string &$formatter_id,
  array &$formatter_settings
): void {
  // Remove the description from the field before it is created.
  // @see schemadotorg_descriptions_schemadotorg_property_field_alter()
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
  $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeBuilderInterface $schema_type_builder */
  $schema_type_builder = \Drupal::service('schemadotorg.schema_type_builder');

  // Check Schema.org property and subtype for description.
  $property_definition = $schema_type_manager->getProperty($schema_property);
  if ($property_definition) {
    $description = $schema_type_builder->formatComment($property_definition['comment'], ['base_path' => 'https://schema.org/']);
  }
  elseif ($schema_property === 'subtype') {
    $description = \Drupal::configFactory()
      ->get('schemadotorg_subtype.settings')
      ->get('default_field_description');
  }
  else {
    $description = NULL;
  }

  // Unset the field's description if it has not been altered.
  if ($field_values['description'] === $description) {
    $field_values['description'] = '';
  }
}

/**
 * Alter Schema.org mapping entity default values.
 *
 * @param array $defaults
 *   The Schema.org mapping entity default values.
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string|null $bundle
 *   The bundle.
 * @param string $schema_type
 *   The Schema.org type.
 */
function hook_schemadotorg_mapping_defaults_alter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void {
  // Add custom subtype property to a Schema.org mapping defaults.
  // @see schemadotorg_subtype_schemadotorg_mapping_defaults_alter()
  /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schema_type_manager */
  $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
  $allowed_values = $schema_type_manager->getAllTypeChildrenAsOptions($schema_type);
  if (empty($allowed_values)) {
    return;
  }

  // Add subtype as a custom Schema.org property.
  $defaults['properties']['subtype'] = [];

  // Handle existing subtype property mapping.
  /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
  $mapping = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping')->load("$entity_type_id.$bundle");
  if ($mapping && $mapping->hasSchemaPropertyMapping('subtype')) {
    $defaults['properties']['subtype']['name'] = $mapping->getSchemaPropertyFieldName('subtype');
    return;
  }

  $config = \Drupal::configFactory()->get('schemadotorg_subtype.settings');
  $label = $config->get('default_field_label');
  $description = $config->get('default_field_description');
  $default_subtypes = $config->get('default_subtypes');

  // Get the field name which can either be _add_ or empty.
  // This value is displayed via a checkbox.
  $name = (!$mapping && in_array($schema_type, $default_subtypes))
    ? \Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD
    : '';

  // Get machine name with subtype suffix.
  /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
  $schema_names = \Drupal::service('schemadotorg.names');
  $machine_name_suffix = $config->get('default_field_suffix');
  $machine_name_max_length = $schema_names->getNameMaxLength('properties') - strlen($machine_name_suffix);
  $options = [
    'maxlength' => $machine_name_max_length,
    'truncate' => TRUE,
  ];
  $machine_name = $bundle ?: $schema_names->camelCaseToDrupalName($schema_type, $options);
  $machine_name .= $machine_name_suffix;

  // Sets the Schema.org mapping defaults for creating a subtype property.
  $defaults['properties']['subtype']['name'] = $name;
  $defaults['properties']['subtype']['type'] = 'list_string';
  $defaults['properties']['subtype']['label'] = $label;
  $defaults['properties']['subtype']['machine_name'] = $machine_name;
  $defaults['properties']['subtype']['description'] = $description;
  $defaults['properties']['subtype']['allowed_values'] = $allowed_values;
}

/**
 * @} End of "addtogroup hooks".
 */
