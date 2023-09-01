<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface defining a Schema.org mapping entity.
 *
 * @see \Drupal\Core\Entity\Display\EntityDisplayInterface
 */
interface SchemaDotOrgMappingInterface extends ConfigEntityInterface {

  /**
   * Gets the entity type for which this mapping is used. (i.e. node)
   *
   * @return string
   *   The entity type ID.
   */
  public function getTargetEntityTypeId(): string;

  /**
   * Gets the bundle to be mapped. (i.e. page)
   *
   * @return string
   *   The bundle to be mapped.
   */
  public function getTargetBundle(): string;

  /**
   * Sets the bundle to be mapped.
   *
   * @param string $bundle
   *   The name of the bundle to be mapped.
   *
   * @return $this
   */
  public function setTargetBundle(string $bundle): SchemaDotOrgMappingInterface;

  /**
   * Gets the entity type definition. (i.e. node annotation)
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type definition.
   */
  public function getTargetEntityTypeDefinition(): ?EntityTypeInterface;

  /**
   * Gets the entity type's bundle ID. (i.e. node_type)
   *
   * @return string|null
   *   The entity type's bundle ID.
   */
  public function getTargetEntityTypeBundleId(): ?string;

  /**
   * Gets the entity type's bundle definition. (i.e. node_type annotation)
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   Get the entity type's bundle definition.
   */
  public function getTargetEntityTypeBundleDefinition(): ?EntityTypeInterface;

  /**
   * Gets the bundle entity type. (i.e. node_type:page)
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityBundleBase|null
   *   The bundle entity type.
   */
  public function getTargetEntityBundleEntity(): ?ConfigEntityBundleBase;

  /**
   * Determine if the entity type supports bundling.
   *
   * @return bool
   *   TRUE if the entity type supports bundling.
   */
  public function isTargetEntityTypeBundle(): bool;

  /**
   * Determine if a new bundle entity is being created.
   *
   * @return bool
   *   TRUE if a new bundle entity is being created.
   */
  public function isNewTargetEntityTypeBundle(): bool;

  /**
   * Gets the Schema.org type to be mapped.
   *
   * @return string|null
   *   The Schema.org type to be mapped.
   */
  public function getSchemaType(): ?string;

  /**
   * Sets the Schema.org type to be mapped.
   *
   * @param string $type
   *   The Schema.org type to be mapped.
   *
   * @return $this
   */
  public function setSchemaType(string $type): SchemaDotOrgMappingInterface;

  /**
   * Gets the mappings for Schema.org properties.
   *
   * @return array
   *   The array of Schema.org property mappings, keyed by field name.
   */
  public function getSchemaProperties(): array;

  /**
   * Gets the original mappings for Schema.org properties.
   *
   * @return array
   *   The array of original Schema.org property mappings, keyed by field name.
   */
  public function getOriginalSchemaProperties(): array;

  /**
   * Sets the original mappings for Schema.org properties.
   *
   * @param array $properties
   *   The array of original Schema.org property mappings, keyed by field name.
   */
  public function setOriginalSchemaProperties(array $properties): void;

  /**
   * Gets the new mappings for Schema.org properties.
   *
   * @return array
   *   The array of new Schema.org property mappings, keyed by field name.
   */
  public function getNewSchemaProperties(): array;

  /**
   * Gets the mapping set for a Schema.org property.
   *
   * @param string $name
   *   The name of the property.
   *
   * @return string|null
   *   The mapping for the Schema.org property, or NULL if the
   *   Schema.org property is not mapped.
   */
  public function getSchemaPropertyMapping(string $name): ?string;

  /**
   * Sets the mapping for a Schema.org property.
   *
   * @param string $name
   *   The field name.
   * @param string $property
   *   The Schema.org property.
   *
   * @return $this
   */
  public function setSchemaPropertyMapping(string $name, string $property): SchemaDotOrgMappingInterface;

  /**
   * Removes the Schema.org property mapping.
   *
   * @param string $name
   *   The name of the Schema.org property mapping.
   *
   * @return $this
   */
  public function removeSchemaProperty(string $name): SchemaDotOrgMappingInterface;

  /**
   * Gets the field name for a Schema.org property.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return string|null
   *   The field name for a Schema.org property.
   */
  public function getSchemaPropertyFieldName(string $property): ?string;

  /**
   * Determine if a Schema.org property is mapped to a Drupal field.
   *
   * @param string $property
   *   The Schema.org property.
   *
   * @return bool
   *   TRUE if a Schema.org property is mapped to a Drupal field.
   */
  public function hasSchemaPropertyMapping(string $property): bool;

  /**
   * Get the Schema.org mapping default values.
   *
   * @return array
   *   The Schema.org mapping default values.
   */
  public function getMappingDefaults(): array;

  /**
   * Set the Schema.org mapping default values.
   *
   * @param array $mapping_defaults
   *   The Schema.org mapping default values.
   *
   * @return $this
   */
  public function setMappingDefaults(array $mapping_defaults): SchemaDotOrgMappingInterface;

  /**
   * Load by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The Schema.org mapping entity.
   */
  public static function loadByEntity(EntityInterface $entity): ?SchemaDotOrgMappingInterface;

}
