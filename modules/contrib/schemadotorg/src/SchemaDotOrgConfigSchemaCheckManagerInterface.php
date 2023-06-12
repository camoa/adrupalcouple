<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

/**
 * Schema.org config schema check manager interface.
 */
interface SchemaDotOrgConfigSchemaCheckManagerInterface {

  /**
   * Check schema compliance in configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   * @param mixed $value
   *   Value to associate with the key.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function checkConfigValue(string $config_name, string $key, mixed $value): bool|array;

}
