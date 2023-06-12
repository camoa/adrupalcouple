<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_preview;

/**
 * Schema.org JSON-LD preview access checker interface.
 */
interface SchemaDotOrgJsonLdPreviewAccessCheckerInterface {

  /**
   * Checks JSON-LD preview access for the current user and route.
   *
   * @return bool
   *   TRUE if the current user can access the preview.
   */
  public function access(): bool;

}
