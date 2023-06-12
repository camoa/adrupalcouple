<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Schema.org JSON-LD builder interface.
 */
interface SchemaDotOrgJsonLdBuilderInterface {

  /**
   * Build JSON-LD for a route.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface|null $route_match
   *   A route match.
   *
   * @return array|bool
   *   The JSON-LD for a route or NULL if the route does not return JSON-LD.
   */
  public function build(?RouteMatchInterface $route_match = NULL): ?array;

  /**
   * Build JSON-LD for an entity that is mapped to a Schema.org type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array|bool
   *   The JSON-LD for an entity that is mapped to a Schema.org type
   *   or NULL if the entity is not mapped to a Schema.org type.
   */
  public function buildEntity(EntityInterface $entity): ?array;

  /**
   * Get Schema.org property values from field items.
   *
   * @param string $schema_type
   *   The Schema.org type.
   * @param string $schema_property
   *   The Schema.org property.
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   *
   * @return array
   *   An array of Schema.org property values.
   */
  public function getSchemaPropertyFieldItems(string $schema_type, string $schema_property, FieldItemListInterface $items): array;

}
