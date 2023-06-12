<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides convenience methods for Schema.org subtype assertions.
 */
trait SchemaDotOrgTestSubtypeTrait {

  /**
   * Create Schema.org subtype field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   */
  protected function createSchemaDotOrgSubTypeField(string $entity_type_id, string $schema_type): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schema_names */
    $schema_names = $this->container->get('schemadotorg.names');
    $bundle = $schema_names->camelCaseToSnakeCase($schema_type);

    /** @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManager $schema_type_manager */
    $schema_type_manager = \Drupal::service('schemadotorg.schema_type_manager');
    $allowed_values = $schema_type_manager->getAllTypeChildrenAsOptions($schema_type);

    FieldStorageConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => 'schema_' . $bundle . '_subtype',
      'type' => 'list_string',
      'allowed_values' => $allowed_values,
    ])->save();

    FieldConfig::create([
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'field_name' => 'schema_' . $bundle . '_subtype',
    ])->save();
  }

}
