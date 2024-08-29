<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Archiver\Tar;
use Drupal\Core\Config\ConfigDirectoryNotDefinedException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\system\MenuInterface;
use Drupal\user\RoleInterface;

/**
 * Provides a trait for functional Config Overlay tests.
 *
 * This should only be used by tests extending BrowserTestBase.
 *
 * Classes using this should install the Config Overlay module and declare a
 * $collections property.
 *
 * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase::$collections
 */
trait ConfigOverlayTestTrait {

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected ConfigManagerInterface $configManager;

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected StorageInterface $configStorage;

  /**
   * The configuration synchronization directory.
   *
   * @var string
   */
  protected string $configSyncDirectory;

  /**
   * The configuration synchronization storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected StorageInterface $configSyncStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The serializer.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected SerializationInterface $serializer;

  /**
   * The profile extension list.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected ProfileExtensionList $profileExtensionList;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\ConfigDirectoryNotDefinedException
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configManager = $this->container->get('config.manager');
    $this->configStorage = $this->container->get('config.storage');
    $this->configSyncStorage = $this->container->get('config.storage.sync');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->fileSystem = $this->container->get('file_system');
    $this->moduleHandler = $this->container->get('module_handler');
    $this->serializer = $this->container->get('serialization.yaml');
    $this->profileExtensionList = $this->container->get('extension.list.profile');

    /* @see \Drupal\Core\Config\FileStorageFactory::getSync() */
    $this->configSyncDirectory = Settings::get('config_sync_directory', FALSE);
    if ($this->configSyncDirectory === FALSE) {
      throw new ConfigDirectoryNotDefinedException('The config sync directory is not defined in $settings["config_sync_directory"]');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  protected function configImporter() {
    // The parent implementation dos not call the import storage transformer.
    $import_transformer = $this->container->get('config.import_transformer');
    assert($import_transformer instanceof ImportStorageTransformer);
    $sync_storage = $import_transformer->transform(
      $this->container->get('config.storage.sync'),
    );
    $storage_comparer = new StorageComparer(
      $sync_storage,
      $this->container->get('config.storage'),
    );

    // The configuration importer is primarily used to test that the proper
    // changes are detected, so we prepare the change list here.
    $storage_comparer->createChangelist();

    return new ConfigImporter(
      $storage_comparer,
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme'),
    );
  }

  /**
   * Tests configuration export after profile installation.
   *
   * @throws \Exception
   */
  public function testConfigExport(): void {
    $this->doTestInitialConfig();
    if (in_array('config', array_keys($this->getModules()))) {
      $this->doTestExportTarball();
    }

    // Recreate the menu with the same values (but for the UUID). This will not
    // be detected as a change.
    $menu_storage = $this->entityTypeManager->getStorage('menu');
    $initial_menu = $menu_storage->load('account');
    $this->assertInstanceof(MenuInterface::class, $initial_menu);
    $recreated_menu = $this->doTestRecreateInitial($initial_menu);

    $this->assertSame('User account menu', $recreated_menu->label());
    $this->doTestEdit($recreated_menu);
    // Edit the menu again, to make sure that this is detected as an update.
    $this->doTestEdit($recreated_menu);
    $this->doTestRecreateAgain($recreated_menu);
  }

  /**
   * Checks that the configuration export is correct after installation.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  protected function doTestInitialConfig(): void {
    $uris = $this->exportConfig();
    foreach ($this->getExpectedConfig() as $collection => $all_config) {
      $this->assertArrayHasKey($collection, $uris);

      $collection_message = ($collection === StorageInterface::DEFAULT_COLLECTION) ? '' : "Collection: $collection, ";
      foreach ($all_config as $name => $config) {
        $this->assertArrayHasKey($name, $uris[$collection]);

        $uri = $uris[$collection][$name];
        $this->assertSame($config, $this->readConfigFile($uri), "{$collection_message}Configuration name: $name");

        unset($uris[$collection][$name]);
      }

      // This is functionally equivalent to Inspector::assertEmpty(), but
      // yields a more expressive error message in case of failure.
      foreach ($uris[$collection] as $name => $uri) {
        $this->assertSame([], $this->readConfigFile($uri), "{$collection_message}Unexpected configuration: $name");
      }

      unset($uris[$collection]);
    }

    // This is functionally equivalent to Inspector::assertEmpty(), but
    // yields a more expressive error message in case of failure.
    foreach ($uris as $collection => $all_config) {
      $this->assertSame([], array_keys($all_config), 'Unexpected collection: ' . $collection);
    }

    // Make sure that the configuration storage comparer detects no changes.
    $this->assertConfigStorageChanges();
  }

  /**
   * Checks that the configuration export tarball is correct.
   *
   * @see \Drupal\Tests\config\Functional\ConfigExportUITest
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Archiver\ArchiverException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestExportTarball(): void {
    $user = $this->drupalCreateUser(['export configuration']);
    $this->drupalLogin($user);

    // Submit the export form and verify the response. This will create a file
    // in the temporary directory with the default name config.tar.gz.
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->submitForm([], 'Export');
    $this->assertSession()->statusCodeEquals(200);

    // Extract the archive and verify it's not empty.
    $temp_directory = $this->fileSystem->getTempDirectory();
    $file_path = $temp_directory . '/config.tar.gz';
    $archiver = new Tar($file_path);
    $archived_paths = $archiver->listContents();
    $extension = $this->serializer->getFileExtension();
    $expected_config = $this->getExpectedConfig();
    // The creation of the administrative user created a role that will be part
    // of the export.
    $roleItems = $user->get('roles');
    $this->assertInstanceOf(EntityReferenceFieldItemListInterface::class, $roleItems);
    $roles = $roleItems->referencedEntities();
    foreach ($roles as $role) {
      // The actual config is not checked here.
      $expected_config[StorageInterface::DEFAULT_COLLECTION][$role->getConfigDependencyName()] = [];
    }
    foreach ($expected_config as $collection => $all_config) {
      /* @see \Drupal\Core\Config\FileStorage::getCollectionDirectory() */
      $prefix = ($collection === StorageInterface::DEFAULT_COLLECTION) ?
        '' :
        str_replace('.', '/', $collection) . '/';
      foreach (array_keys($all_config) as $name) {
        $archived_path = $prefix . $name . '.' . $extension;
        $this->assertContains($archived_path, $archived_paths);
        $archived_paths = array_diff($archived_paths, [$archived_path]);
      }
    }

    $this->assertEmpty($archived_paths);

    // Remove the added role.
    foreach ($roles as $role) {
      $role->delete();
    }
  }

  /**
   * Tests recreating a shipped configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $initial_entity
   *   The entity to recreate.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The recreated entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Config\StorageTransformerException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestRecreateInitial(ConfigEntityInterface $initial_entity): ConfigEntityInterface {
    // Change some configuration and make sure that it is detected correctly.
    $config_name = $initial_entity->getConfigDependencyName();
    $this->assertExportNotHasConfig($config_name);

    $updated_config = [];
    $deleted_config = [];
    /* @see block_menu_delete() */
    if ($initial_entity->getEntityTypeId() === 'menu' && $this->moduleHandler->moduleExists('block')) {
      $block_storage = $this->entityTypeManager->getStorage('block');
      $block_ids = $block_storage
        ->getQuery()
        ->condition('plugin', 'system_menu_block:' . $initial_entity->id())
        ->execute();

      if ($block_ids) {
        $updated_config[] = 'config_overlay.deleted';
        foreach ($block_ids as $block_id) {
          $deleted_config[] = "block.block.$block_id";
        }
      }
    }

    $recreated_entity = $this->recreateEntity($initial_entity);

    $this->assertConfigStorageChanges([], $updated_config, $deleted_config);

    $this->assertExportNotHasConfig($config_name);

    return $recreated_entity;
  }

  /**
   * Tests editing a configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to edit.
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestEdit(ConfigEntityInterface $entity): void {
    // Edit the menu, so that it will be exported to the synchronization
    // directory.
    $label_key = $entity->getEntityType()->getKey('label');
    $entity
      ->set($label_key, $entity->label() . ' EDITED')
      ->save();

    $config_name = $entity->getConfigDependencyName();
    $this->assertConfigStorageChanges([], [$config_name]);
    $uris = $this->assertExportHasConfig($config_name);
    $config = $this->readConfigFile($uris[StorageInterface::DEFAULT_COLLECTION][$config_name]);
    $this->assertArrayHasKey($label_key, $config);
    $this->assertSame($entity->label(), $config[$label_key]);
    $this->assertArrayHasKey('uuid', $config);
  }

  /**
   * Tests recreating a non-shipped configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $recreated_entity
   *   The entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Config\StorageTransformerException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doTestRecreateAgain(ConfigEntityInterface $recreated_entity): void {
    // Recreate the menu again with the same values (but for the UUID). Since
    // the menu has been exported with its UUID, this will now be detected as
    // a recreation.
    $this->recreateEntity($recreated_entity);

    $config_name = $recreated_entity->getConfigDependencyName();
    $this->assertConfigStorageChanges([$config_name], [], [$config_name]);
    $uris = $this->assertExportHasConfig($config_name);
    $recreated_menu_config = $this->readConfigFile($uris[StorageInterface::DEFAULT_COLLECTION][$config_name]);
    $this->assertArrayHasKey('uuid', $recreated_menu_config);
    $this->assertNotEquals($recreated_entity->uuid(), $recreated_menu_config['uuid']);
    $this->assertArrayHasKey('id', $recreated_menu_config);
    $this->assertSame($recreated_entity->id(), $recreated_menu_config['id']);
  }

  /**
   * Exports configuration for all collections and returns the exported files.
   *
   * @return string[][]
   *   A nested array where the keys are the collection names and the values are
   *   mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function exportConfig(): array {
    $this->container->set('config.storage.export', NULL);
    $export_storage = $this->container->get('config.storage.export');
    self::replaceStorageContents($export_storage, $this->configSyncStorage);

    $extension = $this->serializer->getFileExtension();
    $files = $this->fileSystem->scanDirectory($this->configSyncDirectory, "/.\.$extension$/");

    // Build a list of URIs per configuration name and per collection.
    $uris = [];
    foreach ($files as $uri => $file) {
      $path = substr($uri, strlen($this->configSyncDirectory . '/'));
      if (!str_contains($path, '/')) {
        $collection = StorageInterface::DEFAULT_COLLECTION;
      }
      else {
        $parts = explode('/', $path);
        array_pop($parts);
        $collection = implode('.', $parts);
      }

      $uris += [$collection => []];
      $uris[$collection][$file->name] = $file->uri;
    }
    return $uris;
  }

  /**
   * Recreates a given configuration entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   *   The recreated entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function recreateEntity(ConfigEntityInterface $entity): ConfigEntityInterface {
    $entity->delete();
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $values = $entity->toArray();
    unset($values['uuid']);
    $recreated_entity = $entity_storage->create($values);
    $recreated_entity->save();
    return $recreated_entity;
  }

  /**
   * Gets an array of expected configuration that will be exported.
   *
   * This should only contain files that have overridden configuration. For
   * those it should contain the entire configuration file.
   *
   * @return array[]
   *   An array of expected configuration where the keys are the configuration
   *   names and the values are arrays which contain the expected configuration.
   *
   * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait::getOverriddenConfig()
   * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait::getUri()
   */
  protected function getExpectedConfig(): array {
    $all_overridden_config = $this->getOverriddenConfig();

    $expected_config = [];
    foreach ($all_overridden_config as $collection => $collection_overridden_config) {
      $expected_config[$collection] = [];

      // Read the initial config for all configuration that is overridden and
      // merge in the overridden values below.
      foreach ($collection_overridden_config as $config_name => $overridden_config) {
        if ($uri = $this->getUri($collection, $config_name)) {
          $initial_data = $this->readConfigFile($uri);

          // Add the default config hash.
          $other_data = ['_core' => ['default_config_hash' => Crypt::hashBase64(serialize($initial_data))]];
          /* @see \Drupal\Core\Config\ConfigInstaller::createConfiguration() */
          if (isset($overridden_config['langcode'])) {
            $other_data['langcode'] = $overridden_config['langcode'];
          }
          $initial_data = $other_data + $initial_data;

          if ($this->configManager->getEntityTypeIdByName($config_name)) {
            $initial_data = $this->processConfigEntityData($config_name, $initial_data);
          }

          $overridden_config = NestedArray::mergeDeepArray(
            [$initial_data, $overridden_config],
            TRUE
          );
        }
        $expected_config[$collection][$config_name] = $overridden_config;
      }
    }

    return $expected_config;
  }

  /**
   * Returns the overridden configuration for this test.
   *
   * @return array[]
   *   An array of overridden configuration where the keys are the configuration
   *   names and the values are arrays which contain the overridden portions of
   *   the configuration.
   */
  protected function getOverriddenConfig(): array {
    $site_config = $this->configStorage->read('system.site');

    // The core.extension will always be overridden.
    $overridden_config = [];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION] = [];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension'] = [
      'module' => module_config_sort($this->getModules()),
      'theme' => [
        $this->defaultTheme => 0,
      ],
      'profile' => $this->profile,
    ];

    /* @see \Drupal\Core\Test\FunctionalTestSetupTrait::initConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.date'] = [
      'timezone' => [
        'default' => 'Australia/Sydney',
      ],
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.logging'] = [
      'error_level' => ERROR_REPORTING_DISPLAY_VERBOSE,
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.mail'] = [
      'interface' => [
        'default' => 'test_mail_collector',
      ],
    ];
    if (version_compare(\Drupal::VERSION, '10.2.0-dev', '>=')) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.mail']['mailer_dsn'] = [
        'scheme' => 'null',
        'host' => 'null',
        'user' => NULL,
        'password' => NULL,
        'port' => NULL,
        'options' => [],
      ];
    }
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.performance'] = [
      'css' => [
        'preprocess' => FALSE,
      ],
      'js' => [
        'preprocess' => FALSE,
      ],
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site'] = [
      'uuid' => $site_config['uuid'],
      /* @see \Drupal\Core\Test\FunctionalTestSetupTrait::installParameters() */
      'name' => 'Drupal',
      'mail' => 'simpletest@example.com',
    ];

    // Account for various install-time configuration modifications of modules.
    $extension_config = $this->configStorage->read('core.extension');
    /* @see locale_install() */
    if (isset($extension_config['module']['locale'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['locale.settings']['translation']['path'] = $this->siteDirectory . '/files/translations';
    }
    /* @see shortcut_themes_installed() */
    if (isset($extension_config['module']['shortcut'], $extension_config['theme']['seven'])) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['seven.settings'] = [
        'third_party_settings' => [
          'shortcut' => ['module_link' => TRUE],
        ],
      ];
    }
    /* @see user_user_role_insert() */
    $roles = $this->configStorage->listAll('user.role.');
    $roles_to_ignore = [
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ];
    foreach ($roles as $config_name) {
      $role_id = substr($config_name, strlen('user.role.'));
      if (in_array($role_id, $roles_to_ignore, TRUE)) {
        continue;
      }

      $label = $this->configStorage->read($config_name)['label'];
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["system.action.user_add_role_action.$role_id"] = $this->processConfigEntityData("system.action.user_add_role_action.$role_id", [
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'config' => ["user.role.$role_id"],
          'module' => ['user'],
        ],
        'id' => "user_add_role_action.$role_id",
        'label' => "Add the $label role to the selected user(s)",
        'type' => 'user',
        'plugin' => 'user_add_role_action',
        'configuration' => [
          'rid' => $role_id,
        ],
      ]);
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["system.action.user_remove_role_action.$role_id"] = $this->processConfigEntityData("system.action.user_remove_role_action.$role_id", [
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [
          'config' => ["user.role.$role_id"],
          'module' => ['user'],
        ],
        'id' => "user_remove_role_action.$role_id",
        'label' => "Remove the $label role from the selected user(s)",
        'type' => 'user',
        'plugin' => 'user_remove_role_action',
        'configuration' => [
          'rid' => $role_id,
        ],
      ]);
    }

    return $overridden_config;
  }

  /**
   * The base list of modules that will be installed in this test.
   *
   * This list does not contain the list of modules installed by the
   * installation profile.
   *
   * This method may be called during test set-up.
   *
   * @return int[]
   *   The list of module weights, keyed by the respective module names.
   */
  protected function getBaseModules(): array {
    $database_info = Database::getConnectionInfo()['default'];
    if (version_compare(\Drupal::VERSION, '10.2.0-dev', '>=')) {
      /* @see \Drupal\Core\Database\Database::getConnectionInfoAsUrl() */
      $database_module = explode('\\', $database_info['namespace'])[1];
    }
    else {
      $database_module = $database_info['driver'];
    }

    // Required modules, the database module and the profile will always be
    // installed.
    $modules = [
      'path_alias' => 0,
      'system' => 0,
      'user' => 0,
      $database_module => 0,
      /* @see install_finished() */
      $this->profile => 1000,
    ];
    return $modules;
  }

  /**
   * The list of modules that will be installed in this test.
   *
   * This list contains the list of modules installed by the installation
   * profile.
   *
   * This method may not be called during test set-up, use
   * ConfigOverlayTestingTrait::getBaseModules() for that.
   *
   * @return int[]
   *   The list of module weights, keyed by the respective module names.
   *
   * @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait::getBaseModules()
   */
  protected function getModules(): array {
    $modules = $this->getBaseModules();

    // Add any dependencies listed explicitly by the profile.
    $profileInfo = $this->profileExtensionList->get($this->profile)->info;
    $additionalDependencies = $profileInfo['install'] ?? [];

    // Also add any modules explicitly installed by the test.
    /* @see \Drupal\Core\Test\FunctionalTestSetupTrait::installModulesFromClassProperty() */
    $class = static::class;
    while ($class) {
      if (property_exists($class, 'modules')) {
        $additionalDependencies = array_merge($additionalDependencies, $class::$modules);
      }
      $class = get_parent_class($class);
    }

    $prefixToRemove = 'drupal:';
    $moduleWeights = [
      /* @see content_translation_install() */
      'content_translation' => 10,
      /* @see forum_install() */
      'forum' => 1,
      /* @see views_install() */
      'views' => 10,
    ];
    foreach ($additionalDependencies as $additionalDependency) {
      if (str_starts_with($additionalDependency, $prefixToRemove)) {
        $additionalDependency = substr($additionalDependency, strlen($prefixToRemove));
      }
      $modules[$additionalDependency] = $moduleWeights[$additionalDependency] ?? 0;
    }

    // Add recursive dependencies for specific modules, so they do not need to
    // be added by various subclasses.
    if (isset($modules['locale'])) {
      $modules += [
        'field' => 0,
        'file' => 0,
        'language' => 0,
      ];
    }
    if (isset($modules['menu_link_content'])) {
      $modules += [
        'link' => 0,
      ];
    }
    if (isset($modules['node'])) {
      $modules += [
        'field' => 0,
        'filter' => 0,
        'text' => 0,
      ];
    }
    if (isset($modules['sdc'])) {
      $modules += [
        'serialization' => 0,
      ];
    }

    return $modules;
  }

  /**
   * Gets the file URI for the given configuration name.
   *
   * @param string $collection
   *   The collection of the configuration object.
   * @param string $config_name
   *   The configuration name to return the file URI for.
   *
   * @return string|false
   *   The file URI for the given configuration, or FALSE if no configuration
   *   file was found.
   */
  protected function getUri(string $collection, string $config_name) {
    if ($collection === StorageInterface::DEFAULT_COLLECTION) {
      $collection_directory = '';
    }
    else {
      $collection_directory = str_replace('.', '/', $collection) . '/';
    }

    $extension = $this->serializer->getFileExtension();

    // Reverse the list of directories so that the profile directory comes first
    // so that any profile-provided configuration will be used instead of
    // the respective module-provided configuration.
    $directories = array_reverse($this->moduleHandler->getModuleDirectories());
    $directories[] = $this->root . '/core';

    foreach ($directories as $directory) {
      foreach (['install', 'optional'] as $subdirectory) {
        $uri = "$directory/config/$subdirectory/$collection_directory$config_name.$extension";
        if (file_exists($uri)) {
          return $uri;
        }
      }
    }

    return FALSE;
  }

  /**
   * Reads a single configuration file.
   *
   * @param string $uri
   *   The URI of the configuration file.
   *
   * @return array|null
   *   An array of configuration data contained in the file or NULL if the file
   *   is empty.
   */
  protected function readConfigFile(string $uri) {
    return $this->serializer->decode(file_get_contents($uri));
  }

  /**
   * Processes configuration entity data so that it matches the exported state.
   *
   * @param string $config_name
   *   The configuration name.
   * @param array $data
   *   The configuration entity data.
   *
   * @return array
   *   The processed configuration entity data.
   */
  protected function processConfigEntityData(string $config_name, array $data): array {
    // Add the UUID from the active configuration.
    $top_data = ['uuid' => $this->configStorage->read($config_name)['uuid']];

    // Configuration entities are ordered in a particular way when
    // exported, so we need to recreate that here.
    $top_properties = [
      'langcode',
      'status',
      'dependencies',
      'third_party_settings',
      '_core',
    ];
    foreach ($top_properties as $property) {
      if (isset($data[$property])) {
        $top_data[$property] = $data[$property];
      }
    }

    return $top_data + $data;
  }

  /**
   * Asserts configuration storage changes.
   *
   * @param array $create
   *   An array of names of created configuration.
   * @param array $update
   *   An array of names of updated configuration.
   * @param array $delete
   *   An array of names of deleted configuration.
   * @param array $rename
   *   An array of renamed configuration in the format "old_name::new_name".
   *
   * @throws \Drupal\Core\Config\StorageTransformerException
   */
  protected function assertConfigStorageChanges(array $create = [], array $update = [], array $delete = [], array $rename = []): void {
    $actual = $this->configImporter()->getStorageComparer()->getChangelist();
    // When active configuration is deleted we want to detect is as such for the
    // purposes of this test. The configuration importer, however, detects this
    // as configuration to be created when (re-)importing. Thus, we reverse the
    // creations and deletions here, to keep the expectations of the tests while
    // still being able to use the configuration importer.
    $expected = [
      'create' => $delete,
      'update' => $update,
      'delete' => $create,
      'rename' => $rename,
    ];
    $this->assertSame($expected, $actual);
  }

  /**
   * Asserts that a configuration name is among the exported files.
   *
   * @param string $config_name
   *   The name of the configuration object.
   * @param array $collections
   *   (optional) The list of collections that the configuration should be
   *   present in. Defaults to only the default collection.
   *
   * @return string[][]
   *   A nested array where the keys are the collection names and the values are
   *   mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function assertExportHasConfig(string $config_name, array $collections = [StorageInterface::DEFAULT_COLLECTION]): array {
    $uris = $this->exportConfig();
    $this->assertCount(count($this->collections), $uris);
    foreach ($collections as $collection) {
      $this->assertArrayHasKey($collection, $uris);
      $this->assertArrayHasKey($config_name, $uris[$collection]);
    }
    assert(isset($collection));
    foreach (array_diff($this->collections, [$collection]) as $other_collection) {
      $this->assertArrayHasKey($other_collection, $uris);
      $this->assertArrayNotHasKey($config_name, $uris[$other_collection]);
    }

    return $uris;
  }

  /**
   * Asserts that a configuration name is not among the exported files.
   *
   * @param string $config_name
   *   The name of the configuration object.
   *
   * @return string[][]
   *   A nested array where the keys are the collection names and the values are
   *   mappings of configuration names to the respective URIs of the
   *   configuration files.
   */
  protected function assertExportNotHasConfig(string $config_name): array {
    $uris = $this->exportConfig();
    $this->assertCount(count($this->collections), $uris);
    foreach ($this->collections as $collection) {
      $this->assertArrayHasKey($collection, $uris);
      $this->assertArrayNotHasKey($config_name, $uris[$collection]);
    }

    return $uris;
  }

}
