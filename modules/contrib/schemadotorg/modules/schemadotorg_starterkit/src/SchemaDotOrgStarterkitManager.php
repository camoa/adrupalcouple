<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_starterkit;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_rewrite\ConfigRewriter;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\schemadotorg\Traits\SchemaDotOrgDevelGenerateTrait;

/**
 * Schema.org Starterkit manager service.
 */
class SchemaDotOrgStarterkitManager implements SchemaDotOrgStarterkitManagerInterface {
  use SchemaDotOrgDevelGenerateTrait;

  /**
   * Constructs a SchemaDotOrgStarterkitManager object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   The module extension list.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\config_rewrite\ConfigRewriter|null $configRewriter
   *   The configuration rewrite.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schemaMappingManager
   *   The Schema.org mapping manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schemaConfigManager
   *   The Schema.org config manager.
   * @param \Drupal\devel_generate\DevelGeneratePluginManager|null $develGenerateManager
   *   The Devel generate manager.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected ModuleExtensionList $extensionListModule,
    protected ModuleInstallerInterface $moduleInstaller,
    protected ConfigFactoryInterface $configFactory,
    protected ?ConfigRewriter $configRewriter,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgMappingManagerInterface $schemaMappingManager,
    protected SchemaDotOrgConfigManagerInterface $schemaConfigManager,
    protected ?DevelGeneratePluginManager $develGenerateManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function isStarterkit(string $module): bool {
    $module_path = $this->extensionListModule->getPath($module);
    $module_schemadotorg_path = "$module_path/$module.schemadotorg_starterkit.yml";
    return file_exists($module_schemadotorg_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getStarterkit($module): ?array {
    return $this->getStarterkits()[$module] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getStarterkits(): array {
    $modules = $this->extensionListModule->getAllAvailableInfo();
    foreach ($modules as $module_name => $module_info) {
      if (!str_starts_with($module_name, 'schemadotorg_')
        || !$this->isStarterkit($module_name)) {
        unset($modules[$module_name]);
        continue;
      }
    }
    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function getStarterkitSettings(string $module): FALSE|array {
    $module_path = $this->extensionListModule->getPath($module);
    $module_schemadotorg_path = "$module_path/$module.schemadotorg_starterkit.yml";
    if (!file_exists($module_schemadotorg_path)) {
      return FALSE;
    }

    $settings = Yaml::decode(file_get_contents($module_schemadotorg_path));
    return ($settings !== TRUE) ? $settings : [];
  }

  /**
   * Install a Schema.org starterkit.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function install(string $module): void {
    $this->moduleInstaller->install([$module]);
  }

  /**
   * Generate a Schema.org starterkit's content.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function generate(string $module): void {
    $settings = $this->getStarterkitSettings($module);
    $types = array_keys($settings['types']);
    $this->develGenerate($types, 5);
  }

  /**
   * Kill a Schema.org starterkit's content.
   *
   * @param string $module
   *   A Schema.org starterkit module name.
   */
  public function kill(string $module): void {
    $settings = $this->getStarterkitSettings($module);
    $types = array_keys($settings['types']);
    $this->develGenerate($types, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function preinstall(string $module): void {
    if (!$this->isStarterkit($module)) {
      return;
    }

    $this->rewriteSchemaConfig($module);
    $this->setupSchemaTypes($module);
  }

  /**
   * {@inheritdoc}
   */
  public function installed(array $modules): void {
    if (!$this->configRewriter) {
      return;
    }

    $has_schema_config_rewrite = FALSE;
    foreach ($modules as $module) {
      if (!$this->isStarterkit($module)) {
        continue;
      }
      $module_path = $this->extensionListModule->getPath($module);
      $rewrite_dir = "$module_path/config/rewrite";
      $has_schema_config_rewrite = file_exists($rewrite_dir)
        && $this->fileSystem->scanDirectory($rewrite_dir, '/^schemadotorg.*\.yml$/i', ['recurse' => FALSE]);
      if ($has_schema_config_rewrite) {
        break;
      }
    }

    // Repair configuration if the starterkit has written any
    // schemadotorg* configuration.
    // @see https://www.drupal.org/project/config_rewrite/issues/3152228
    if ($has_schema_config_rewrite) {
      $this->schemaConfigManager->repair();
    }
  }

  /**
   * Rewrite Schema.org Blueprints related configuration.
   *
   * Scan the rewrite directory for schemadotorg.* config rewrites that need
   * to be installed before any Schema.org types are created.
   *
   * @param string $module
   *   A module.
   */
  protected function rewriteSchemaConfig(string $module): void {
    if (is_null($this->configRewriter)) {
      return;
    }

    $module_path = $this->extensionListModule->getPath($module);
    $rewrite_dir = "$module_path/config/rewrite";
    if (!file_exists($rewrite_dir)) {
      return;
    }

    $files = $this->fileSystem->scanDirectory($rewrite_dir, '/^schemadotorg.*\.yml$/i', ['recurse' => FALSE]) ?: [];
    if (empty($files)) {
      return;
    }

    foreach ($files as $file) {
      $contents = file_get_contents($rewrite_dir . DIRECTORY_SEPARATOR . $file->name . '.yml');
      $rewrite = Yaml::decode($contents);
      $config = $this->configFactory->getEditable($file->name);
      $original_data = $config->getRawData();
      $rewrite = $this->configRewriter->rewriteConfig($original_data, $rewrite, $file->name, $module);
      $config->setData($rewrite)->save();
    }
  }

  /**
   * Set up a starterkit  module based on the module's settings.
   *
   * @param string $module
   *   A module.
   */
  protected function setupSchemaTypes(string $module): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping');

    $settings = $this->getStarterkitSettings($module);
    $types = $settings['types'] ?? [];
    foreach ($types as $type => $defaults) {
      [$entity_type_id, $schema_type] = explode(':', $type);
      $mapping = $mapping_storage->loadBySchemaType($entity_type_id, $schema_type);
      if ($mapping) {
        // Don't allow properties to be unexpectedly removed.
        $defaults['properties'] = array_filter($defaults['properties']);
        $bundle = $mapping->getTargetBundle();
        $mapping_defaults = $this->schemaMappingManager->getMappingDefaults($entity_type_id, $bundle, $schema_type, $defaults);
        $this->schemaMappingManager->saveMapping($entity_type_id, $schema_type, $mapping_defaults);
      }
      else {
        $this->schemaMappingManager->createType($entity_type_id, $schema_type, $defaults);
      }
    }
  }

}
