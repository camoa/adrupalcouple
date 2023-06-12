<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

/**
 * Schema.org entity relationship manager interface.
 */
interface SchemaDotOrgEntityRelationshipManagerInterface {

  /**
   * Repair relationships.
   *
   * @return array
   *   An array of messages.
   */
  public function repair(): array;

}
