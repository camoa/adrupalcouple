<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_cer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;

/**
 * Schema.orgCorresponding Entity Reference manager.
 */
class SchemaDotOrgCorrespondingReferenceManager implements SchemaDotOrgCorrespondingReferenceManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgCorrespondingReferenceManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MessengerInterface $messenger,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void {
    if ($entity_type_id !== 'node') {
      return;
    }

    $default_properties = $this->configFactory
      ->get('schemadotorg_cer.settings')
      ->get('default_properties');
    $default_properties += array_flip($default_properties);
    foreach ($default_properties as $default_property) {
      if (isset($defaults['properties'][$default_property])) {
        $defaults['properties'][$default_property]['type'] = 'field_ui:entity_reference:node';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    $entity_type_id = $mapping->getTargetEntityTypeId();
    if ($entity_type_id !== 'node') {
      return;
    }

    $schema_property_field_names = [];
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping');
    $mapping_ids = $mapping_storage->getQuery()
      ->condition('target_entity_type_id', 'node')
      ->execute();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $mapping_storage->loadMultiple($mapping_ids);
    foreach ($mappings as $mapping) {
      $schema_property_field_names += array_flip($mapping->getSchemaProperties());
    }

    $default_properties = $this->configFactory
      ->get('schemadotorg_cer.settings')
      ->get('default_properties');
    foreach ($default_properties as $first_property_name => $second_property_name) {
      $first_field_name = $schema_property_field_names[$first_property_name] ?? NULL;
      $second_field_name = $schema_property_field_names[$second_property_name] ?? NULL;

      // Make sure that this is a valid corresponding entity reference.
      if (!$this->isValidCorrespondingReferenceFields($first_field_name, $second_field_name)) {
        continue;
      }

      // Make sure the corresponding entity reference does not already exist.
      /** @var \Drupal\cer\CorrespondingReferenceStorageInterface $corresponding_reference_storage */
      $corresponding_reference_storage = $this->entityTypeManager
        ->getStorage('corresponding_reference');
      if ($corresponding_reference_storage->load($first_field_name)) {
        continue;
      }

      // Create corresponding entity reference.
      $first_property_definition = $this->schemaTypeManager->getProperty($first_property_name);
      $second_property_definition = $this->schemaTypeManager->getProperty($second_property_name);
      $label = 'Schema.org: '
        . $first_property_definition['drupal_label']
        . ' ↔ '
        . $second_property_definition['drupal_label'];
      $corresponding_reference_storage->create([
        'id' => $first_field_name,
        'label' => $label,
        'enabled' => TRUE,
        'first_field' => $first_field_name,
        'second_field' => $second_field_name,
        'add_direction' => 'append',
        'bundles' => ['node' => ['*']],
      ])->save();

      // Display message.
      $this->messenger->addStatus($this->t('Created %label corresponding entity reference.', ['%label' => $label]));
    }
  }

  /**
   * Sync entity references target bundles for all Schema.org mappings..
   */
  public function syncEntityReferencesTargetBundles(): void {
    $field_name_bundles = [];
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface[] $mappings */
    $mappings = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping')
      ->loadByProperties(['target_entity_type_id' => 'node']);
    foreach ($mappings as $mapping) {
      foreach (array_keys($mapping->getSchemaProperties()) as $field_name) {
        $field_name_bundles += [$field_name => []];
        $field_name_bundles[$field_name][$mapping->getTargetBundle()] = $mapping->getTargetBundle();
      }
    }

    /** @var \Drupal\cer\Entity\CorrespondingReferenceInterface[] $corresponding_references */
    $corresponding_references = $this->entityTypeManager
      ->getStorage('corresponding_reference')
      ->loadMultiple();
    foreach ($corresponding_references as $corresponding_reference) {
      // Make sure that the corresponding reference was generated by this
      // module and uses the first field's id as the corresponding reference id.
      if ($corresponding_reference->id() !== $corresponding_reference->getFirstField()) {
        continue;
      }

      $first_field_name = $corresponding_reference->getFirstField();
      $second_field_name = $corresponding_reference->getSecondField();
      $this->syncEntityReferenceTargetBundles($first_field_name, $second_field_name, $field_name_bundles);
      $this->syncEntityReferenceTargetBundles($second_field_name, $first_field_name, $field_name_bundles);
    }
  }

  /**
   * Sync entity reference target bundles.
   *
   * @param string $source_field_name
   *   The source field name.
   * @param string $target_field_name
   *   The target field name.
   * @param array $field_name_bundles
   *   An associative array tracking field names instances to bundles.
   */
  protected function syncEntityReferenceTargetBundles(string $source_field_name, string $target_field_name, array $field_name_bundles): void {
    $source_bundles = $field_name_bundles[$source_field_name] ?? NULL;
    $target_bundles = $field_name_bundles[$target_field_name] ?? NULL;
    if (empty($source_bundles) || empty($target_bundles)) {
      return;
    }

    ksort($target_bundles);

    /** @var \Drupal\field\FieldConfigStorage $field_config_storage */
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    $ids = $field_config_storage->getQuery()
      ->condition('entity_type', 'node')
      ->condition('bundle', $source_bundles, 'IN')
      ->condition('field_name', $source_field_name)
      ->execute();
    /** @var \Drupal\Core\Field\FieldConfigInterface[] $source_field_configs */
    $source_field_configs = $field_config_storage->loadMultiple($ids);
    foreach ($source_field_configs as $source_field_config) {
      $handler_settings = $source_field_config->getSetting('handler_settings');
      $handler_settings['target_bundles'] = $target_bundles;
      $source_field_config->setSetting('handler_settings', $handler_settings);
      $source_field_config->save();
    }
  }

  /**
   * Check the first and second field name are valid corresponding reference fields.
   *
   * @param string|null $first_field_name
   *   The first corresponding field ID.
   * @param string|null $second_field_name
   *   The second corresponding field ID.
   *
   * @return bool
   *   TRUE is the fields are valid corresponding reference fields.
   */
  protected function isValidCorrespondingReferenceFields(?string $first_field_name, ?string $second_field_name): bool {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_config_storage */
    $field_config_storage = $this->entityTypeManager
      ->getStorage('field_storage_config');

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $fields */
    $fields = $field_config_storage->loadMultiple(["node.$first_field_name", "node.$second_field_name"]);
    if (count($fields) !== 2) {
      return FALSE;
    }

    foreach ($fields as $field) {
      if ($field->getType() !== 'entity_reference'
        || $field->getSetting('target_type') !== 'node') {
        return FALSE;
      }
    }

    return TRUE;
  }

}
