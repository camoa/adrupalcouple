<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_field_group;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org field group entity display builder interface.
 */
interface SchemaDotOrgFieldGroupEntityDisplayBuilderInterface {

  /**
   * Disabled field group patterns.
   */
  const array PATTERNS = [
    ['entity_type_id'],
    ['entity_type_id', 'display_type'],
    ['entity_type_id', 'display_type', 'bundle'],
    ['entity_type_id', 'display_type', 'bundle', 'field_name'],
    ['entity_type_id', 'display_type', 'schema_type'],
    ['entity_type_id', 'display_type', 'schema_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'field_name'],
    ['entity_type_id', 'display_type', 'display_mode'],
    ['entity_type_id', 'display_type', 'display_mode', 'bundle'],
    ['entity_type_id', 'display_type', 'display_mode', 'bundle', 'field_name'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_type'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_type', 'schema_property'],
    ['entity_type_id', 'display_type', 'display_mode', 'schema_property'],
    ['entity_type_id', 'bundle'],
    ['entity_type_id', 'bundle', 'field_name'],
    ['entity_type_id', 'schema_type'],
    ['entity_type_id', 'schema_type', 'schema_property'],
    ['entity_type_id', 'schema_property'],
    ['entity_type_id', 'field_name'],
  ];

  /**
   * Apply function to re-apply field groups a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingApply(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Pre-save function to process and save a Schema.org mapping.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingPreSave(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Pre-save function to set field group on the entity display.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity form or view display.
   */
  public function entityDisplayPreSave(EntityDisplayInterface $display): void;

  /**
   * Alters the entity display edit form by modifying component weights and human-readable names.
   *
   * @param array $form
   *   The renderable form array for the entity display edit form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterEntityDisplayEditForm(array &$form, FormStateInterface $form_state): void;

}
