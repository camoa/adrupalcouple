<?php

declare(strict_types=1);

namespace Drupal\schemadotorg_translation;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;

/**
 * Schema.org translate manager interface.
 */
interface SchemaDotOrgTranslationManagerInterface {

  /**
   * Enable translation for a Schema.org mapping when a mapping is inserted.
   *
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping
   *   The Schema.org mapping.
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void;

  /**
   * Enable translation for a Schema.org mapping field when a field config is inserted.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The field.
   */
  public function fieldConfigInsert(FieldConfigInterface $field_config): void;

}
