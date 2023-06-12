<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org JSON-LD custom manager interface.
 */
interface SchemaDotOrgJsonLdCustomInterface {

  /**
   * Alter Schema.org mapping entity default values.
   *
   * @param array $defaults
   *   The Schema.org mapping entity default values.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|null $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @see hook_schemadotorg_mapping_defaults_alter()
   */
  public function alterMappingDefaults(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void;

  /**
   * Load the Schema.org type JSON-LD data for an entity.
   *
   * Modules can define custom JSON-LD data for any entity type.
   *
   * @param array $data
   *   The Schema.org JSON-LD data for an entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @see hook_schemadotorg_jsonld_schema_type_entity_load()
   */
  public function loadSchemaTypeEntityJsonLd(array &$data, EntityInterface $entity): void;

  /**
   * Provide custom Schema.org JSON-LD data for a route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array|null
   *   Custom Schema.org JSON-LD data.
   *
   * @see hook_schemadotorg_jsonld_custom_schemadotorg_jsonld()
   */
  public function buildRouteMatchJsonLd(RouteMatchInterface $route_match): ?array;

}
