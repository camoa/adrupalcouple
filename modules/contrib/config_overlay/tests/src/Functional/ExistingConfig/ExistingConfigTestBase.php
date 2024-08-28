<?php

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Component\FileSystem\FileSystem;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\FunctionalTests\Installer\InstallerExistingConfigTestBase;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait;

/**
 * Provides a base class for testing existing configuration.
 */
abstract class ExistingConfigTestBase extends InstallerExistingConfigTestBase {

  use ConfigOverlayTestTrait {
    getExpectedConfig as traitGetExpectedConfig;
    getOverriddenConfig as traitGetOverriddenConfig;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_overlay'];

  /**
   * {@inheritdoc}
   */
  protected $existingSyncDirectory = TRUE;

  /**
   * A list of collections for this test's configuration.
   *
   * @var string[]
   */
  protected array $collections = [StorageInterface::DEFAULT_COLLECTION];

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    /* @see \Drupal\config\Controller\ConfigController::downloadExport() */
    // This is called from the test set-up, so we cannot use the file-system
    // service.
    /* @see \Drupal\Core\File\FileSystem::getTempDirectory() */
    $temporaryDirectory = FileSystem::getOsTemporaryDirectory() ?: PublicStream::basePath() . '/tmp';
    $archivePath = tempnam($temporaryDirectory, 'config');
    $archive = new ArchiveTar($archivePath, 'gz');

    // The tarballs contain the following configuration files:
    // - core.extension.yml: With the extensions given by the respective,
    //   profile, the database driver module and Config Overlay.
    // - system.date.yml: To set the default timezone to UTC.
    // - system.site.yml: To set the site UUID, name and mail.
    // This is called from the test set-up, so we cannot
    // ConfigOverlayTestingTrait::getModules().
    $config = [
      'core.extension' => [
        'module' => module_config_sort($this->getBaseModules() + ['config_overlay' => 0]),
        'theme' => [
          'stark' => 0,
        ],
        'profile' => $this->profile,
      ],
      'system.date' => [
        'first_day' => 0,
        'country' => [
          'default' => NULL,
        ],
        'timezone' => [
          'default' => 'UTC',
          'user' => [
            'configurable' => TRUE,
            'default' => 0,
            'warn' => FALSE,
          ],
        ],
      ],
      'system.site' => [
        'langcode' => 'en',
        'uuid' => 'bf34ffa4-5095-4316-9bea-99df28a35e03',
        'name' => 'Site with ' . ucfirst($this->profile) . ' profile and Config Overlay',
        'mail' => 'admin@example.com',
        'slogan' => '',
        'page' => [
          '403' => '',
          '404' => '',
          'front' => '/user/login',
        ],
        'admin_compact_mode' => FALSE,
        'weight_select_max' => 100,
        'default_langcode' => 'en',
        'mail_notification' => NULL,
      ],
    ];

    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      // See https://www.drupal.org/project/drupal/issues/3437325
      $config['system.date']['country']['default'] = '';
      unset($config['system.site']['mail_notification']);
    }

    foreach ($config as $name => $data) {
      $archive->addString("$name.yml", Yaml::encode($data));
    }

    return $archivePath;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig(): array {
    $all_expected_config = $this->traitGetExpectedConfig();

    // The existing configuration will not have a default configuration hash as
    // it is installed through a configuration synchronization.
    /* @see \Drupal\Core\Config\ConfigInstaller::createConfiguration() */
    foreach ($all_expected_config as &$collection_expected_config) {
      foreach ($collection_expected_config as &$expected_config) {
        unset($expected_config['_core']);
      }
    }

    return $all_expected_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = $this->traitGetOverriddenConfig();

    // Tests based on InstallerTestBase do not call
    // BrowserTestBase::installDrupal() and, by extension,
    // FunctionalTestSetupTrait::initConfig(). The change to the 'system.mail'
    // configuration is still performed, however.
    /* @see \Drupal\FunctionalTests\Installer\InstallerTestBase::setUp() */
    unset(
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.logging'],
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.performance'],
    );

    // The existing configuration should always be overridden.
    foreach ($this->getExistingConfigNames() as $config_name) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION] += [
        $config_name => [],
      ];
    }

    return $overridden_config;
  }

  /**
   * Gets a list of configuration names of the existing configuration.
   *
   * @return string[]
   *   A list of configuration names.
   *
   * @see \Drupal\FunctionalTests\Installer\InstallerExistingConfigTestBase::prepareEnvironment()
   */
  protected function getExistingConfigNames(): array {
    $archiver = new ArchiveTar($this->getConfigTarball(), 'gz');
    $list = $archiver->listContent();
    $config_names = [];
    if (is_array($list)) {
      foreach ($list as $file) {
        $config_names[] = basename($file['filename'], '.yml');
      }
    }
    return $config_names;
  }

}
