<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

/**
 * Schema.org config manager interface.
 */
interface SchemaDotOrgConfigManagerInterface {

  /**
   * Update a Schema.org type's default properties.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param array|string|null $add
   *   Schema.org properties to be removed.
   * @param array|string|null $remove
   *   Schema.org properties to be added.
   */
  public function setSchemaTypeDefaultProperties(string $schema_type, array|string|NULL $add = NULL, array|string|NULL $remove = NULL): void;

  /**
   * Update a Schema.org mapping type's default properties.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $schema_type
   *   The Schema.org type.
   * @param array|string|null $add
   *   Schema.org properties to be removed.
   * @param array|string|null $remove
   *   Schema.org properties to be added.
   */
  public function setMappingTypeSchemaTypeDefaultProperties(string $entity_type_id, string $schema_type, array|string|NULL $add = NULL, array|string|NULL $remove = NULL): void;

  /**
   * Repair configuration.
   */
  public function repair(): void;

}
