<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\Entity\ImportableEntityStorageInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for 'schemadotorg_mapping' storage.
 */
interface SchemaDotOrgMappingStorageInterface extends ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Determine if an entity is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity is mapped to a Schema.org type.
   */
  public function isEntityMapped(EntityInterface $entity): bool;

  /**
   * Determine if an entity type and bundle are mapped to a Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return bool
   *   TRUE if an entity type and bundle are mapped to a Schema.org type.
   */
  public function isBundleMapped(string $entity_type_id, string $bundle): bool;

  /**
   * Gets the Schema.org type for an entity and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return string|null
   *   The Schema.org type for an entity and bundle.
   */
  public function getSchemaType(string $entity_type_id, string $bundle): ?string;

  /**
   * Gets the Schema.org property name for an entity field mapping.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The Schema.org property name for an entity field mapping.
   */
  public function getSchemaPropertyName(string $entity_type_id, string $bundle, string $field_name): ?string;

  /**
   * Get a Schema.org property's range includes.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return array
   *   The Schema.org property's range includes.
   */
  public function getSchemaPropertyRangeIncludes(string $schema_type, string $schema_property): array;

  /**
   * Get a Schema.org property's target bundles.
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   *
   * @return array
   *   The Schema.org property's target bundles.
   */
  public function getSchemaPropertyTargetBundles(string $target_type, string $schema_type, string $schema_property): array;

  /**
   * Gets the Schema.org range includes target bundles.
   *
   * @param string $target_type
   *   The target entity type ID.
   * @param array $range_includes
   *   An array of Schema.org types.
   *
   * @return array
   *   The Schema.org range includes target bundles.
   */
  public function getRangeIncludesTargetBundles(string $target_type, array $range_includes): array;

  /**
   * Determine if Schema.org type is mapped to an entity.
   *
   * @param string|null $entity_type_id
   *   The entity type ID.
   * @param string|null $schema_type
   *   The Schema.org type.
   *
   * @return bool
   *   TRUE if Schema.org type is mapped to an entity.
   */
  public function isSchemaTypeMapped(?string $entity_type_id, ?string $schema_type): bool;

  /**
   * Load by target entity id and Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The Schema.org mapping entity.
   */
  public function loadBySchemaType(string $entity_type_id, string $schema_type): ?SchemaDotOrgMappingInterface;

  /**
   * Load by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The Schema.org mapping entity.
   */
  public function loadByEntity(EntityInterface $entity): ?SchemaDotOrgMappingInterface;

}
