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
   *   An associative array of image styles.
   * @param array|null $original_settings
   *   An associative array of original image styles.  Defaults to image
   *   styles saved via configuration.
   */
  public function resetImageStyles(array $settings, ?array $original_settings = NULL): void;

}
