<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_field_group;

/**
 * Schema.org field group entity display builder interface.
 */
interface SchemaDotOrgFieldGroupEntityDisplayBuilderInterface {

  /**
   * Set entity display field groups for Schema.org properties.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array $properties
   *   The Schema.org properties to be added to field groups.
   */
  public function setFieldGroups(string $entity_type_id, string $bundle, string $schema_type, array $properties): void;

}
