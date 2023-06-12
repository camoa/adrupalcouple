<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

/**
 * Schema.org starterkit manager interface.
 */
interface SchemaDotOrgStarterkitManagerInterface {

  /**
   * Determine if a module is Schema.org Blueprints Starterkit.
   *
   * @param string $module
   *   A module.
   *
   * @return bool
   *   TRUE if a module is Schema.org Blueprints Starterkit.
   */
  public function isStarterkit(string $module): bool;

  /**
   * Get a list of Schema.org starterkits.
   *
   * @return array
   *   A list of Schema.org starterkits.
   */
  public function getStarterkits(): array;

  /**
   * Get a Schema.org starterkit's module info.
   *
   * @param string $module
   *   A module name.
   *
   * @return array|null
   *   A Schema.org starterkit's module info.
   */
  public function getStarterkit(string $module): ?array;

  /**
   * Get a module's Schema.org Blueprints starterkit settings.
   *
   * @param string $module
   *   A module name.
   *
   * @return false|array
   *   A module's Schema.org Blueprints starterkit settings.
   *   FALSE if the module is not a Schema.org Blueprints starterkit
   */
  public function getStarterkitSettings(string $module): FALSE|array;

  /**
   * Install a Schema.org starterkit.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function install(string $module): void;

  /**
   * Generate a Schema.org starterkit's content.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function generate(string $module): void;

  /**
   * Kill a Schema.org starterkit's content.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function kill(string $module): void;

  /**
   * Preinstall a Schema.org Blueprints starterkit.
   *
   * @param string $module
   *   A module.
   */
  public function preinstall(string $module): void;

  /**
   * Install a Schema.org Blueprints starterkits.
   *
   * @param array $modules
   *   An array of modules being installed.
   */
  public function installed(array $modules): void;

}
