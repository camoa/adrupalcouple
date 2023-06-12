<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonapi_preview;

/**
 * Schema.org JSON:API preview builder interface.
 */
interface SchemaDotOrgJsonApiPreviewBuilderInterface {

  /**
   * Build JSON:API preview for the current route.
   *
   * @return array|null
   *   The JSON:API preview for the current route.
   */
  public function build(): array|NULL;

}
