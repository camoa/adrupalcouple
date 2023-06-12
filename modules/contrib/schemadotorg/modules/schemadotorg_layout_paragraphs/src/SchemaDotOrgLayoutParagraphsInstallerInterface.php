<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_layout_paragraphs;

/**
 * Schema.org layout paragraphs installer.
 */
interface SchemaDotOrgLayoutParagraphsInstallerInterface {

  /**
   * Install and  generate paragraphs types.
   */
  public function install(): void;

}
