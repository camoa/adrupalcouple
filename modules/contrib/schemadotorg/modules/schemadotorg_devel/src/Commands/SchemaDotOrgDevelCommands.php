<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_devel\Commands;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Schema.org Devel Drush commands.
 */
class SchemaDotOrgDevelCommands extends DrushCommands {

  /**
   * Constructs a SchemaDotOrgDevelCommands object.
   */
  public function __construct(
    protected ModuleExtensionList $moduleExtensionList,
    protected ExtensionPathResolver $extensionPathResolver
  ) {}

  /**
   * Generate MODULE.features.yml for Schema.org Blueprints sub-modules.
   *
   * @command schemadotorg:generate-features
   *
   * @usage schemadotorg:generate-features
   */
  public function generateFeatures(): void {
    if (!$this->io()
      ->confirm(dt('Are you sure you want to generate MODULE.features.yml for all Schema.org Blueprints sub-modules?'))) {
      throw new UserAbortException();
    }

    $module_names = array_keys($this->moduleExtensionList->getAllAvailableInfo());
    $module_names = array_filter($module_names, function ($module_name) {
      return str_starts_with($module_name, 'schemadotorg');
    });
    foreach ($module_names as $module_name) {
      $module_path = $this->extensionPathResolver->getPath('module', $module_name);
      $features_path = "$module_path/$module_name.features.yml";
      if (!file_exists($features_path)) {
        $this->output()->writeln("Creating $features_path.");
        file_put_contents($features_path, 'true' . PHP_EOL);
      }
      else {
        $this->output()->writeln("Skipping $features_path.");
      }
    }
  }

}
