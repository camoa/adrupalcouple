<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "schemadotorg_mapping" configuration entities.
 *
 * The Schema.org mapping storage makes is easier to load and examine
 * Schema.org mappings for types and properties to bundles and fields.
 *
 * This storage service also makes it possible to look up related entity
 * bundles based on Schema.org types.
 */
class SchemaDotOrgMappingStorage extends ConfigEntityStorage implements SchemaDotOrgMappingStorageInterface {

  /**
   * The Schema.org names service.
   */
  protected SchemaDotOrgNamesInterface $schemaNames;

  /**
   * The Schema.org schema type manager.
   */
  protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager;

  /**
   * The Schema.org entity display builder.
   */
  protected SchemaDotOrgEntityDisplayBuilderInterface $schemaEntityDisplayBuilder;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    $instance->schemaNames = $container->get('schemadotorg.names');
    $instance->schemaEntityDisplayBuilder = $container->get('schemadotorg.entity_display_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityMapped(EntityInterface $entity): bool {
    return $this->isBundleMapped($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function isBundleMapped(string $entity_type_id, string $bundle): bool {
    return (boolean) $this->getQuery()
      ->condition('target_entity_type_id', $entity_type_id)
      ->condition('target_bundle', $bundle)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaType(string $entity_type_id, string $bundle): ?string {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $this->load($entity_type_id . '.' . $bundle);
    if (!$entity) {
      return NULL;
    }
    return $entity->getSchemaType();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyName(string $entity_type_id, string $bundle, string $field_name): ?string {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    $entity = $this->load($entity_type_id . '.' . $bundle);
    if (!$entity) {
      return NULL;
    }
    return $entity->getSchemaPropertyMapping($field_name) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyRangeIncludes(string $schema_type, string $schema_property): array {
    $schema_properties_range_includes = $this->configFactory
      ->get('schemadotorg.settings')
      ->get("schema_properties.range_includes");
    $range_includes = $schema_properties_range_includes["$schema_type--$schema_property"]
      ?? $schema_properties_range_includes[$schema_property]
      ?? $this->schemaTypeManager->getPropertyRangeIncludes($schema_property);
    return array_combine($range_includes, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyTargetBundles(string $target_type, string $schema_type, string $schema_property): array {
    $range_includes = $this->getSchemaPropertyRangeIncludes($schema_type, $schema_property);
    return $this->getRangeIncludesTargetBundles($target_type, $range_includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getRangeIncludesTargetBundles(string $target_type, array $range_includes, $ignore_thing = TRUE): array {
    // Ignore 'Thing' because it is too generic.
    if ($ignore_thing) {
      unset($range_includes['Thing']);
    }

    // If the range includes Thing, we can return all the mapping
    // target bundles.
    if (isset($range_includes['Thing'])) {
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
      $mappings = $this->loadByProperties(['target_entity_type_id' => $target_type]);
      $target_bundles = [];
      foreach ($mappings as $mapping) {
        $target_bundle = $mapping->getTargetBundle();
        $target_bundles[$target_bundle] = $target_bundle;
      }
      return $target_bundles;
    }

    $subtypes = $this->schemaTypeManager->getAllSubTypes($range_includes);
    $entity_ids = $this->getQuery()
      ->condition('target_entity_type_id', $target_type)
      ->condition('schema_type', $subtypes, 'IN')
      ->execute();
    if (!$entity_ids) {
      return [];
    }

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->loadMultiple($entity_ids);
    $target_bundles = [];
    foreach ($mappings as $mapping) {
      $target_bundle = $mapping->getTargetBundle();
      $target_bundles[$target_bundle] = $target_bundle;
    }
    return $target_bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function isSchemaTypeMapped(?string $entity_type_id, ?string $schema_type): bool {
    if (empty($entity_type_id) || empty($schema_type)) {
      return FALSE;
    }

    return (boolean) $this->getQuery()
      ->condition('target_entity_type_id', $entity_type_id)
      ->condition('schema_type', $schema_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySchemaType(string $entity_type_id, string $schema_type): ?SchemaDotOrgMappingInterface {
    $entities = $this->loadByProperties([
      'target_entity_type_id' => $entity_type_id,
      'schema_type' => $schema_type,
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity): ?SchemaDotOrgMappingInterface {
    if (!$this->isEntityMapped($entity)) {
      return NULL;
    }

    $entities = $this->loadByProperties([
      'target_entity_type_id' => $entity->getEntityTypeId(),
      'target_bundle' => $entity->bundle(),
    ]);
    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $entity */
    parent::doPostSave($entity, $update);
    if (!$update) {
      $this->schemaEntityDisplayBuilder->setComponentWeights($entity);
    }
  }

}
