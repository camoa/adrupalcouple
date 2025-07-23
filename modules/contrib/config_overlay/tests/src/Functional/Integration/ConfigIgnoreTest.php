<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Integration;

use Drupal\system\MenuInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

/**
 * Tests installing with Config Ignore and Config Overlay.
 *
 * @group config_overlay
 */
class ConfigIgnoreTest extends ConfigOverlayTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_ignore'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function testConfigExport(): void {
    parent::testConfigExport();

    // Configure both a piece of configuration that is overridden (the system
    // date configuration) and a piece of configuration that is not overridden
    // (the Administration menu) to be ignored to make sure that changes to
    // either will not be overridden.
    $this->config('config_ignore.settings')
      ->set('ignored_config_entities', [
        'system.date',
        'system.menu.admin',
      ])
      ->save();
    $this->exportConfig();

    // Change the system date configuration and make sure that no change is
    // detected.
    $this->config('system.date')
      ->set('country.default', 'Australia')
      ->save();
    $this->assertConfigStorageChanges();

    // Make sure that importing the configuration does not revert the change.
    $extension = $this->serializer->getFileExtension();
    $exportedConfiguration = $this->readConfigFile("$this->configSyncDirectory/system.date.$extension");
    $this->assertArrayHasKey('country', $exportedConfiguration);
    $this->assertArrayHasKey('default', $exportedConfiguration['country']);
    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      $this->assertSame('', $exportedConfiguration['country']['default']);
    }
    else {
      $this->assertNull($exportedConfiguration['country']['default']);
    }
    $this->configImporter()->import();
    $this->assertSame(
      'Australia',
      $this->config('system.date')->get('country.default'),
    );

    // Change the admin menu and make sure that no change is detected.
    $menuStorage = $this->entityTypeManager->getStorage('menu');
    $menu = $menuStorage->load('admin');
    $this->assertInstanceof(MenuInterface::class, $menu);
    $labelKey = $menu->getEntityType()->getKey('label');
    $menu
      ->set($labelKey, 'Administration EDITED')
      ->save();
    $this->assertConfigStorageChanges();

    // Make sure that importing the configuration does not revert the change.
    $this->configImporter()->import();
    $menuStorage->resetCache(['admin']);
    $menu = $menuStorage->load('admin');
    $this->assertInstanceof(MenuInterface::class, $menu);
    $this->assertSame('Administration EDITED', $menu->label());

    // Delete the shipped menu and make sure that Config Overlay does not
    // recreate it (because it should be ignored per Config Ignore).
    $menu->delete();
    $this->assertConfigStorageChanges();
    $this->configImporter()->import();
    $menuStorage->resetCache(['admin']);
    $menu = $menuStorage->load('admin');
    $this->assertNull($menu);
  }

}
