<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_taxonomy;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org taxonomy JSON-LD manager interface.
 */
interface SchemaDotOrgTaxonomyJsonLdManagerInterface {

  /**
   * Load Schema.org JSON-LD for an entity.
   *
   * @param array $data
   *   Schema.org type data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function load(array &$data, EntityInterface $entity): void;

  /**
   * Alter Schema.org JSON-LD for an entity.
   *
   * @param array $data
   *   Schema.org type data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function alter(array &$data, EntityInterface $entity): void;

  /**
   * Preprocess HTML alter JSON-LD Term endpoint.
   *
   * @param array $variables
   *   An array of variables.
   */
  public function preprocessHtml(array &$variables): void;

}
