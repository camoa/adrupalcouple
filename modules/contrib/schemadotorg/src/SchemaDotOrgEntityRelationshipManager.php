<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Schema.org entity relationship manager service.
 */
class SchemaDotOrgEntityRelationshipManager implements SchemaDotOrgEntityRelationshipManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgEntityRelationshipsManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function repair(): array {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    /** @var \Drupal\field\FieldConfigStorage $field_storage */
    $field_storage = $this->entityTypeManager->getStorage('field_config');

    $entity_ids = $field_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_type', ['entity_reference', 'entity_reference_revisions'], 'IN')
      ->sort('id')
      ->execute();

    $messages = [];

    /** @var \Drupal\Core\Field\FieldConfigInterface[] $fields */
    $fields = $field_storage->loadMultiple($entity_ids);
    foreach ($fields as $field) {
      $field_name = $field->getName();
      $field_type = $field->getType();
      $entity_type_id = $field->getTargetEntityTypeId();
      $bundle = $field->getTargetBundle();
      /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
      $mapping = $mapping_storage->load("$entity_type_id.$bundle");
      if (!$mapping) {
        continue;
      }

      $schema_type = $mapping->getSchemaType();
      $schema_property = $mapping->getSchemaPropertyMapping($field_name);
      if (!$schema_property) {
        continue;
      }

      // Skip Schema.org property that used to store the main entity.
      if ($this->schemaTypeManager->isPropertyMainEntity($schema_property)) {
        continue;
      }

      // Get expected target bundles.
      $target_type = $field->getSetting('target_type');
      $expected_target_bundles = $mapping_storage->getSchemaPropertyTargetBundles($target_type, $schema_type, $schema_property);

      // Get actual target bundles.
      $handler_settings = $field->getSetting('handler_settings');
      $actual_target_bundles = $handler_settings['target_bundles'] ?? [];

      // Manually sync paragraph:from_library.
      if ($target_type === 'paragraph' && isset($actual_target_bundles['from_library'])) {
        $expected_target_bundles['from_library'] = 'from_library';
      }

      // Skip if expected target bundles is empty.
      if (empty($expected_target_bundles)) {
        continue;
      }

      // Skip if the expected and actual target bundles matches.
      if ($expected_target_bundles == $actual_target_bundles) {
        continue;
      }

      // Update target bundles to match expected.
      $handler_settings['target_bundles'] = $expected_target_bundles;

      // Update paragraph's weighted target bundles to match expected.
      if (isset($handler_settings['target_bundles_drag_drop'])) {
        $weight = 0;
        foreach ($handler_settings['target_bundles'] as $target_bundle) {
          $handler_settings['target_bundles_drag_drop'][$target_bundle] = [
            'weight' => $weight,
            'enabled' => TRUE,
          ];
          $weight++;
        }
      }

      $field->setSetting('handler_settings', $handler_settings);
      $field->save();

      // Display success message.
      $t_args = [
        '@entity_type' => $entity_type_id,
        '@field_name' => $field_name,
        '@field_type' => $field_type,
        '@schema_type' => $schema_type,
        '@schema_property' => $schema_property,
        '@bundles' => implode(', ', $expected_target_bundles),
      ];
      $messages[] = $this->t("Updated @entity_type:@field_name (@schema_type:@schema_property) '@field_type' field to target '@bundles'.", $t_args);
    }
    return $messages;
  }

}
