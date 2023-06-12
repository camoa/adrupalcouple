<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Defines the Schema.org mapping entity.
 *
 * @ConfigEntityType(
 *   id = "schemadotorg_mapping",
 *   label = @Translation("Schema.org mapping"),
 *   label_collection = @Translation("Schema.org mappings"),
 *   label_singular = @Translation("Schema.org mapping"),
 *   label_plural = @Translation("Schema.org mappings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Schema.org mapping",
 *     plural = "@count Schema.org mappings",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\schemadotorg\SchemaDotOrgMappingStorage",
 *     "list_builder" = "Drupal\schemadotorg\SchemaDotOrgMappingListBuilder",
 *     "form" = {
 *       "add" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingForm",
 *       "edit" = "Drupal\schemadotorg\Form\SchemaDotOrgMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "schemadotorg_mapping",
 *   admin_permission = "administer schemadotorg",
 *   links = {
 *     "collection" = "/admin/config/search/schemadotorg-mapping",
 *     "add-form" = "/admin/config/search/schemadotorg-mapping/add",
 *     "edit-form" = "/admin/config/search/schemadotorg-mapping/{schemadotorg_mapping}",
 *     "delete-form" = "/admin/config/search/schemadotorg-mapping/{schemadotorg_mapping}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_export = {
 *     "id",
 *     "target_entity_type_id",
 *     "target_bundle",
 *     "schema_type",
 *     "schema_properties",
 *   }
 * )
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewDisplay
 */
class SchemaDotOrgMapping extends ConfigEntityBase implements SchemaDotOrgMappingInterface {

  /**
   * Unique ID for the config entity.
   *
   * @var string
   */
  protected $id;

  /**
   * Entity type to be mapped.
   *
   * @var string
   */
  protected $target_entity_type_id;

  /**
   * Bundle to be mapped.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * Schema.org type.
   *
   * @var string
   */
  protected $schema_type;

  /**
   * List of Schema.org property mappings, keyed by field name.
   *
   * @var array
   */
  protected $schema_properties = [];

  /**
   * List of original Schema.org property mappings.
   *
   * @var array
   */
  protected $original_schema_properties = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->setOriginalSchemaProperties($this->getSchemaProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    parent::postSave($storage, $update);
    $this->setOriginalSchemaProperties($this->getSchemaProperties());
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();
    $duplicate->setOriginalSchemaProperties($this->getSchemaProperties());
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->target_entity_type_id . '.' . $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->isTargetEntityTypeBundle()
      ? $this->getTargetEntityBundleEntity()->label()
      : $this->getTargetEntityTypeDefinition()->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->target_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): string {
    return $this->isTargetEntityTypeBundle()
      ? $this->target_bundle
      : $this->getTargetEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetBundle($bundle): SchemaDotOrgMappingInterface {
    $this->set('target_bundle', $bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeDefinition(): ?EntityTypeInterface {
    return $this->entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeBundleId(): ?string {
    return $this->getTargetEntityTypeDefinition()->getBundleEntityType();
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeBundleDefinition(): ?EntityTypeInterface {
    $bundle_entity_type = $this->getTargetEntityTypeBundleId();
    return $bundle_entity_type ? $this->entityTypeManager()->getDefinition($bundle_entity_type) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityBundleEntity(): ?ConfigEntityBundleBase {
    if (!$this->isTargetEntityTypeBundle()) {
      return NULL;
    }

    $bundle = $this->getTargetBundle();
    $bundle_entity_type_id = $this->getTargetEntityTypeBundleId();
    $entity_storage = $this->entityTypeManager()->getStorage($bundle_entity_type_id);
    return $bundle ? $entity_storage->load($bundle) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isTargetEntityTypeBundle(): bool {
    return (boolean) $this->getTargetEntityTypeBundleId();
  }

  /**
   * {@inheritdoc}
   */
  public function isNewTargetEntityTypeBundle(): bool {
    return ($this->isTargetEntityTypeBundle() && !$this->getTargetEntityBundleEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaType(): ?string {
    return $this->schema_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaType($type): SchemaDotOrgMappingInterface {
    $this->schema_type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaProperties(): array {
    return $this->schema_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalSchemaProperties(): array {
    return $this->original_schema_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalSchemaProperties(array $properties): void {
    $this->original_schema_properties = $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewSchemaProperties(): array {
    return array_diff_key($this->schema_properties, $this->original_schema_properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyMapping($name): ?string {
    return $this->schema_properties[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchemaPropertyMapping(string $name, string $property): SchemaDotOrgMappingInterface {
    $this->schema_properties[$name] = $property;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSchemaProperty($name): SchemaDotOrgMappingInterface {
    unset($this->schema_properties[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaPropertyFieldName(string $property): ?string {
    $schema_properties = array_flip($this->schema_properties);
    return $schema_properties[$property] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSchemaPropertyMapping(string $property): bool {
    return in_array($property, $this->schema_properties);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): SchemaDotOrgMappingInterface {
    parent::calculateDependencies();
    $target_entity_type = $this->entityTypeManager()->getDefinition($this->target_entity_type_id);

    // Create dependency on the bundle.
    $bundle_config_dependency = $target_entity_type->getBundleConfigDependency($this->getTargetBundle());
    $this->addDependency($bundle_config_dependency['type'], $bundle_config_dependency['name']);

    // If field.module is enabled, add dependencies on 'field_config' entities
    // for both displayed and hidden fields. We intentionally leave out base
    // field overrides, since the field still exists without them.
    if (\Drupal::moduleHandler()->moduleExists('field')) {
      $properties = $this->schema_properties;
      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->getTargetEntityTypeId(), $this->getTargetBundle());
      foreach (array_intersect_key($field_definitions, $properties) as $field_definition) {
        if ($field_definition instanceof ConfigEntityInterface && $field_definition->getEntityTypeId() === 'field_config') {
          $this->addDependency('config', $field_definition->getConfigDependencyName());
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies): bool {
    $changed = parent::onDependencyRemoval($dependencies);
    foreach ($dependencies['config'] as $entity) {
      if ($entity->getEntityTypeId() === 'field_config') {
        /** @var \Drupal\field\FieldConfigInterface $entity */
        // Remove properties for fields that are being deleted.
        if ($this->getSchemaPropertyMapping($entity->getName())) {
          $this->removeSchemaProperty($entity->getName());
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

  /**
   * Returns the plugin dependencies being removed.
   *
   * The function recursively computes the intersection between all plugin
   * dependencies and all removed dependencies.
   *
   * Note: The two arguments do not have the same structure.
   *
   * @param array[] $plugin_dependencies
   *   A list of dependencies having the same structure as the return value of
   *   ConfigEntityInterface::calculateDependencies().
   * @param array[] $removed_dependencies
   *   A list of dependencies having the same structure as the input argument of
   *   ConfigEntityInterface::onDependencyRemoval().
   *
   * @return array
   *   A recursively computed intersection.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies()
   * @see \Drupal\Core\Config\Entity\ConfigEntityInterface::onDependencyRemoval()
   */
  protected function getPluginRemovedDependencies(array $plugin_dependencies, array $removed_dependencies): array {
    $intersect = [];
    foreach ($plugin_dependencies as $type => $dependencies) {
      if ($removed_dependencies[$type]) {
        // Config and content entities have the dependency names as keys while
        // module and theme dependencies are indexed arrays of dependency names.
        // @see \Drupal\Core\Config\ConfigManager::callOnDependencyRemoval()
        if (in_array($type, ['config', 'content'])) {
          $removed = array_intersect_key($removed_dependencies[$type], array_flip($dependencies));
        }
        else {
          $removed = array_values(array_intersect($removed_dependencies[$type], $dependencies));
        }
        if ($removed) {
          $intersect[$type] = $removed;
        }
      }
    }
    return $intersect;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntity(EntityInterface $entity): ?SchemaDotOrgMappingInterface {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
    return $mapping_storage->loadByEntity($entity);
  }

}
