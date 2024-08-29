<?php

namespace Drupal\Tests\config_overlay\Functional\Integration;

use Drupal\config_split\ConfigSplitManager;
use Drupal\config_split\Entity\ConfigSplitEntityInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\File\FileSystemInterface;
use Drupal\system\MenuInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

// cspell:ignore stackable

/**
 * Tests installing with Config Split and Config Overlay.
 *
 * @group config_overlay
 */
class ConfigSplitTest extends ConfigOverlayTestBase {

  /**
   * The configuration split manager.
   *
   * @var \Drupal\config_split\ConfigSplitManager
   */
  protected ConfigSplitManager $configSplitManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_overlay_test_config_split',
    'config_split',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configSplitManager = $this->container->get('config_split.manager');
  }

  /**
   * {@inheritdoc}
   */
  protected function exportConfig(): array {
    $uris = parent::exportConfig();

    /* @see \Drupal\config_split\ConfigSplitCliService::postExportAll() */
    $this->configSplitManager->commitAll();

    return $uris;
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigExport(): void {
    parent::testConfigExport();

    /* @see \Drupal\Core\Installer\Form\SiteSettingsForm::createRandomConfigDirectory() */
    $splitDirectory = dirname($this->configSyncDirectory) . '/split';
    $this->assertTrue($this->fileSystem->prepareDirectory($splitDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS));

    // Split off a module and both a piece of configuration that is overridden
    // (the system date configuration) and a piece of configuration that is not
    // overridden (the Administration menu) into a separate directory next to
    // the "sync" directory that was created by the installer.
    $splitEntityStorage = $this->entityTypeManager->getStorage('config_split');
    $syncSplitEntity = $splitEntityStorage->create([
      'id' => 'from_sync',
      'storage' => 'folder',
      'folder' => $splitDirectory,
      'module' => [
        'user' => 0,
      ],
      'complete_list' => [
        'system.date',
        'system.menu.admin',
      ],
    ]);
    $syncSplitEntity->save();

    // The test installs a module that ships a configuration split that splits
    // off a module and a piece of configuration.
    $moduleSplitEntity = $splitEntityStorage->load('from_module');
    $this->assertInstanceOf(ConfigSplitEntityInterface::class, $moduleSplitEntity);
    $this->assertSame(['field' => 0], $moduleSplitEntity->get('module'));
    $this->assertSame(
      ['core.date_format.html_date'],
      $moduleSplitEntity->get('complete_list'),
    );

    $this->exportConfig();

    // Test that the split modules are not in the exported extension
    // configuration.
    $extension = $this->serializer->getFileExtension();
    $this->assertFileExists("$this->configSyncDirectory/core.extension.$extension");
    $exportedExtensionConfiguration = $this->readConfigFile("$this->configSyncDirectory/core.extension.$extension");
    $this->assertArrayHasKey('module', $exportedExtensionConfiguration);
    $this->assertArrayHasKey('system', $exportedExtensionConfiguration['module']);
    $this->assertArrayNotHasKey('field', $exportedExtensionConfiguration['module']);
    $this->assertArrayNotHasKey('user', $exportedExtensionConfiguration['module']);

    // Test that the split configuration, including configuration shipped by
    // the modules that are split off, is not exported to the synchronization
    // directory.
    $this->assertFileDoesNotExist("$this->configSyncDirectory/core.date_format.html_date.$extension");
    $this->assertFileDoesNotExist("$this->configSyncDirectory/field.settings.$extension");
    $this->assertFileDoesNotExist("$this->configSyncDirectory/system.date.$extension");
    $this->assertFileDoesNotExist("$this->configSyncDirectory/system.menu.admin.$extension");
    $this->assertFileDoesNotExist("$this->configSyncDirectory/user.role.anonymous.$extension");
    $this->assertFileDoesNotExist("$this->configSyncDirectory/user.settings.$extension");

    // Test that the configuration from the 'from_sync' split was split into the
    // split directory.
    $this->assertFileExists("$splitDirectory/system.date.$extension");
    $this->assertFileExists("$splitDirectory/system.menu.admin.$extension");
    $this->assertFileExists("$splitDirectory/user.role.anonymous.$extension");
    $this->assertFileExists("$splitDirectory/user.settings.$extension");
    // Test that the configuration from the 'from_module' split was not split
    // into the split directory as it uses the database storage.
    $this->assertFileDoesNotExist("$splitDirectory/core.date_format.html_date.$extension");
    $this->assertFileDoesNotExist("$splitDirectory/field.settings.$extension");

    // Now make the 'from_sync' split stack-able, so that shipped configuration
    // from the User module, which it splits off, is no longer exported to the
    // split directory.
    $syncSplitEntity->set('stackable', TRUE)->save();
    // Read the menu configuration into memory, before it is removed.
    $exportedMenuConfiguration = $this->readConfigFile("$splitDirectory/system.menu.admin.$extension");
    $this->exportConfig();
    $this->assertFileDoesNotExist("$splitDirectory/user.role.anonymous.$extension");
    $this->assertFileDoesNotExist("$splitDirectory/user.settings.$extension");

    // Change the configuration that is split off and make sure that it can be
    // imported correctly. Use the file storage instead of manually writing the
    // files so that the static file cache is updated correctly.
    $splitConfigStorage = new FileStorage($splitDirectory);
    $exportedDateConfiguration = $this->readConfigFile("$splitDirectory/system.date.$extension");
    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      $this->assertSame('', $exportedDateConfiguration['country']['default']);
    }
    else {
      $this->assertNull($exportedDateConfiguration['country']['default']);
    }
    $exportedDateConfiguration['country']['default'] = 'Australia';
    $splitConfigStorage->write('system.date', $exportedDateConfiguration);

    $this->assertSame('Administration', $exportedMenuConfiguration['label']);
    $exportedMenuConfiguration['label'] = 'Administration EDITED';
    $splitConfigStorage->write('system.menu.admin', $exportedMenuConfiguration);

    $this->assertConfigStorageChanges([], ['system.date', 'system.menu.admin']);
    $this->configImporter()->import();

    $this->assertSame(
      'Australia',
      $this->config('system.date')->get('country.default'),
    );

    $menu = $this->entityTypeManager->getStorage('menu')->load('admin');
    $this->assertInstanceof(MenuInterface::class, $menu);
    $this->assertSame('Administration EDITED', $menu->label());
  }

}
