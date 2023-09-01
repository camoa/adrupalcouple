<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_identifier;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_field_group\SchemaDotOrgFieldGroupEntityDisplayBuilderInterface;

/**
 * Schema.org identifier manager.
 */
class SchemaDotOrgIdentifierManager implements SchemaDotOrgIdentifierManagerInterface {

  /**
   * Constructs a SchemaDotOrgIdentifierManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder
   *   The Schema.org entity type builder.
   * @param \Drupal\schemadotorg_field_group\SchemaDotOrgFieldGroupEntityDisplayBuilderInterface|null $fieldGroupEntityDisplayBuilder
   *   The Schema.org field group entity display builder.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder,
    protected ?SchemaDotOrgFieldGroupEntityDisplayBuilderInterface $fieldGroupEntityDisplayBuilder
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    $schema_type = $mapping->getSchemaType();
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    // Build the field definition.
    $identifier_field_definitions = $this->getMappingFieldDefinitions($mapping);
    $properties = [];
    foreach ($identifier_field_definitions as $identifier_field_definition) {
      // Skip existing base fields.
      if (!empty($identifier_field_definition['base_field'])) {
        continue;
      }

      $field_name = $identifier_field_definition['field_name'];
      $identifier_field_definition += [
        'machine_name' => $field_name,
        'type' => 'string',
        'schema_type' => $schema_type,
        'schema_property' => 'identifier:' . $identifier_field_definition['property_id'],
      ];
      $this->entityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $identifier_field_definition);

      $properties[$field_name] = 'identifier';
    }

    // Set identifiers into field groups.
    if ($this->fieldGroupEntityDisplayBuilder) {
      $this->fieldGroupEntityDisplayBuilder->setFieldGroups($mapping, $properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingFieldDefinitions(SchemaDotOrgMappingInterface $mapping): array {
    $config = $this->configFactory->get('schemadotorg_identifier.settings');

    $entity_type_id = $mapping->getTargetEntityTypeId();
    $schema_type = $mapping->getSchemaType();

    // Get Schema.org type identifier field names.
    $field_names = [];
    $identifier_schema_types = $config->get('schema_types');
    foreach ($identifier_schema_types as $identifier_schema_type => $identifier_schema_type_field_names) {
      if ($this->schemaTypeManager->isSubTypeOf($schema_type, $identifier_schema_type)) {
        $field_names += array_combine($identifier_schema_type_field_names, $identifier_schema_type_field_names);
      }
    }

    // Get Schema.org type identifier field definitions.
    $field_definitions = $config->get('field_definitions');
    $field_definitions = array_intersect_key($field_definitions, $field_names);
    if (empty($field_definitions)) {
      return [];
    }

    // Add defaults to the field definitions.
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    foreach ($field_definitions as $field_name => &$field_definition) {
      $field_definition += [
        'field_name' => $field_name,
        'property_id' => $field_name,
        'base_field' => isset($base_field_definitions[$field_name]),
      ];
    }

    return $field_definitions;
  }

}
