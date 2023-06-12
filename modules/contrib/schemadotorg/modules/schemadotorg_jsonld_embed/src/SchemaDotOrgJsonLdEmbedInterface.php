<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_embed;

use Drupal\Core\Entity\EntityInterface;

/**
 * Schema.org JSON-LD embed manager interface.
 */
interface SchemaDotOrgJsonLdEmbedInterface {

  /**
   * Build embedded media and content entity JSON-LD data.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The embedded media and content entity JSON-LD data.
   */
  public function build(EntityInterface $entity): array;

}
