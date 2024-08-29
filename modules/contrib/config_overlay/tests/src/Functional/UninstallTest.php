<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Config Overlay can be correctly uninstalled.
 *
 * @group config_overlay
 */
class UninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected StorageInterface $configStorage;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected ModuleInstallerInterface $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configStorage = $this->container->get('config.storage');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Tests installing and uninstalling Config Overlay.
   */
  public function testUninstall() {
    // Before the module is installed, its configuration should not exist.
    $this->assertFalse($this->configStorage->exists('config_overlay.deleted'));

    // Installing the module should install its configuration.
    $this->moduleInstaller->install(['config_overlay']);
    $this->assertTrue($this->configStorage->exists('config_overlay.deleted'));
    $this->assertDeletedConfigList([]);

    // Uninstalling should remove it again.
    $this->moduleInstaller->uninstall(['config_overlay']);
    $this->assertFalse($this->configStorage->exists('config_overlay.deleted'));

    // Make sure that re-installing the module is possible.
    $this->moduleInstaller->install(['config_overlay']);
    $this->assertTrue($this->configStorage->exists('config_overlay.deleted'));
    $this->assertDeletedConfigList([]);

    // Make sure that installing and uninstalling another module, does not
    // affect the list of deleted configuration.
    $this->moduleInstaller->install(['automated_cron']);
    $this->assertTrue($this->configStorage->exists('automated_cron.settings'));
    $this->assertDeletedConfigList([]);

    $this->moduleInstaller->uninstall(['automated_cron']);
    $this->assertFalse($this->configStorage->exists('automated_cron.settings'));
    $this->assertDeletedConfigList([]);
  }

  /**
   * Asserts that the list of deleted configuration is as expected.
   *
   * @param string[] $expected
   *   The list of expected deleted configuration names.
   */
  protected function assertDeletedConfigList(array $expected): void {
    $config = $this->configStorage->read('config_overlay.deleted');
    $this->assertIsArray($config);
    $this->assertArrayHasKey('names', $config);
    $this->assertSame($expected, $config['names']);
  }

}
