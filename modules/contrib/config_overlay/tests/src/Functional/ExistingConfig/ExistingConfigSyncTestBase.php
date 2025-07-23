<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides a base class for testing existing synchronization configuration.
 */
class ExistingConfigSyncTestBase extends ExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    $profileDirectory = "$this->siteDirectory/profiles/$this->profile";
    /* @see \Drupal\FunctionalTests\Installer\InstallerConfigDirectoryTestBase::prepareEnvironment() */
    mkdir($profileDirectory, 0777, TRUE);
    file_put_contents("$profileDirectory/$this->profile.info.yml", Yaml::encode([
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Configuration installation test profile (' . $this->profile . ')',
    ]));

    mkdir("$profileDirectory/config/install", 0777, TRUE);
    file_put_contents(
      "$profileDirectory/config/install/system.date.yml",
      Yaml::encode($this->getSystemDateConfiguration()),
    );
    file_put_contents(
      "$profileDirectory/config/install/system.mail.yml",
      Yaml::encode($this->getSystemMailConfiguration()),
    );

    mkdir("$profileDirectory/config/sync", 0777, TRUE);
    file_put_contents(
      "$profileDirectory/config/sync/core.extension.yml",
      Yaml::encode($this->getCoreExtensionConfiguration()),
    );
    file_put_contents(
      "$profileDirectory/config/sync/system.site.yml",
      Yaml::encode($this->getSystemSiteConfiguration()),
    );

    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => "$profileDirectory/config/sync",
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->writeSettings([
      'settings' => [
        'config_sync_directory' => (object) [
          'value' => "$this->siteDirectory/config/sync",
          'required' => TRUE,
        ],
      ],
    ]);
    $this->rebuildContainer();
    $this->configSyncDirectory = "$this->siteDirectory/config/sync";
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    return [
      StorageInterface::DEFAULT_COLLECTION => [],
    ];
  }

}
