<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_custom_field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.org Custom Field manager.
 */
class SchemaDotOrgCustomFieldManager implements SchemaDotOrgCustomFieldManagerInterface {

  /**
   * Constructs a SchemaDotOrgCustomFieldManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface $schemaEntityFieldManager
   *   The Schema.org entity field manager.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $customFieldTypeManager
   *   The custom field type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityFieldManagerInterface $schemaEntityFieldManager,
    protected CustomFieldTypeManagerInterface $customFieldTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function propertyFieldTypeAlter(array &$field_types, string $schema_type, string $schema_property): void {
    $default_properties = $this->getDefaultProperties($schema_type, $schema_property);
    if ($default_properties) {
      $field_types = ['custom' => 'custom'] + $field_types;
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
  public function propertyFieldPrepare(array &$default_field, string $schema_type, string $schema_property): void {
    // Make sure the main entity field has a unique name by prefixing it with
    // the bundle name.
    $default_properties = $this->getDefaultProperties($schema_type, $schema_property);
    if ($default_properties && $schema_property === 'mainEntity') {
      $default_type = $this->configFactory
        ->get('schemadotorg.settings')
        ->get("schema_types.default_types.$schema_type") ?? [];
      $type_definition = $this->schemaTypeManager->getType($schema_type);

      $type_name = $default_type['name'] ?? $type_definition['drupal_name'];
      $field_name = $default_field['name'];
      $default_field['name'] = $type_name . '_' . $field_name;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function propertyFieldAlter(
    string $schema_type,
    string $schema_property,
    array &$field_storage_values,
    array &$field_values,
    ?string &$widget_id,
    array &$widget_settings,
    ?string &$formatter_id,
    array &$formatter_settings
  ): void {
    // Make sure the field type is set to 'custom' (field).
    if ($field_storage_values['type'] !== 'custom') {
      return;
    }

    // Check to see if the property has custom field settings.
    $default_properties = $this->getDefaultProperties($schema_type, $schema_property);
    if (!$default_properties) {
      return;
    }

    $custom_field_schema_type = $default_properties['type'] ?? '';
    $custom_field_schema_properties = $default_properties['properties'] ?? [];

    $weight = 0;
    $field_storage_columns = [];
    $field_settings = [];
    foreach ($custom_field_schema_properties as $schema_property => $data_type) {
      $default_field = $this->schemaEntityFieldManager->getPropertyDefaultField($custom_field_schema_type, $schema_property);

      $name = $default_field['name'];
      $label = $default_field['label'];
      $description = $default_field['description'];

      $widget_type = $this->getDefaultWidgetType($data_type);
      $default_widget_settings = $this->getDefaultWidgetSettings($widget_type);

      $field_storage_columns[$name] = [
        'name' => $name,
        'type' => $data_type,
        // Set default storage column values.
        'max_length' => '255',
        'unsigned' => 0,
        'precision' => '10',
        'scale' => '2',
      ];

      $field_settings[$name] = [
        'type' => $widget_type,
        'widget_settings' => [
          'label' => $label,
          'settings' => [
            'description' => $description,
          ] + $default_widget_settings['settings'],
        ],
        'check_empty' => '1',
        'weight' => $weight,
      ];
      $unit = $this->schemaTypeManager->getPropertyUnit($schema_property);
      if ($unit) {
        $field_settings[$name]['widget_settings']['settings']['suffix'] = ' ' . $unit;
        $field_settings[$name]['formatter_settings']['prefix_suffix'] = TRUE;
      }

      $weight++;
    }

    $field_storage_values['settings']['columns'] = $field_storage_columns;

    $field_values['settings'] = [
      'field_settings' => $field_settings,
      'field_type' => 'custom',
    ];

    $widget_id = 'custom_stacked';
    $widget_settings = ['wrapper' => 'fieldset'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(string $schema_type, string $schema_property): ?array {
    $config = $this->configFactory->get('schemadotorg_custom_field.settings');
    return $config->get("default_properties.$schema_type--$schema_property")
      ?? $config->get("default_properties.$schema_property")
      ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldItemSchemaMapping(FieldItemListInterface|FieldItemInterface $item): ?SchemaDotOrgMappingInterface {
    $field_type = $item->getFieldDefinition()->getType();
    return ($field_type === 'custom')
      ? SchemaDotOrgMapping::loadByEntity($item->getEntity())
      : NULL;
  }

  /**
   * Get custom field default widget type for a custom field data type.
   *
   * @param string $data_type
   *   A custom field data type.
   *
   * @return string
   *   A custom field widget type.
   */
  protected function getDefaultWidgetType(string $data_type): string {
    switch ($data_type) {
      case 'string':
        return 'text';

      case 'string_long':
        return 'textarea';

      default:
        return $data_type;
    }
  }

  /**
   * Get custom field default widget settings for a custom field widget type.
   *
   * @param string $widget_type
   *   A custom field widget type.
   *
   * @return array
   *   An associate array of custom field default widget settings.
   */
  protected function getDefaultWidgetSettings(string $widget_type): array {
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $custom_field_type */
    $custom_field_type = $this->customFieldTypeManager->createInstance($widget_type);
    $default_widget_settings = $custom_field_type::defaultWidgetSettings();

    switch ($widget_type) {
      case 'decimal':
      case 'float':
        $default_widget_settings['settings']['scale'] = 2;
        break;

      case 'text':
        $default_widget_settings['settings']['maxlength'] = 255;
        break;

      case 'textarea':
        $default_format = $this->configFactory
          ->get('schemadotorg_custom_field.settings')
          ->get('default_format');
        if ($default_format) {
          $default_widget_settings['settings']['formatted'] = TRUE;
          $default_widget_settings['settings']['default_format'] = $default_format;
          $default_widget_settings['settings']['format'] = [
            'guidelines' => FALSE,
            'help' => FALSE,
          ];
        }
        break;
    }

    $default_widget_settings['settings'] += [
      'description_display' => 'after',
      'required' => FALSE,
    ];

    return $default_widget_settings;
  }

}
