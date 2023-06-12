<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_focal_point;

/**
 * Schema.org Focal Point manager interface.
 */
interface SchemaDotOrgFocalPointManagerInterface {

  /**
   * Reset focal point image styles.
   *
   * @param array $settings
   *   An associative array of image style data keyed by the image style label.
   */
  public function resetImageStyles(array $settings): void;

}
