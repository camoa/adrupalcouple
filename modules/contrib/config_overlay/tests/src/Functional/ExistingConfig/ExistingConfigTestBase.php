<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Config\StorageInterface;
use Drupal\FunctionalTests\Installer\InstallerConfigDirectoryTestBase;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait;
use org\bovigo\vfs\vfsStream;

/**
 * Provides a base class for testing installation from existing configuration.
 */
abstract class ExistingConfigTestBase extends InstallerConfigDirectoryTestBase {

  use ConfigOverlayTestTrait {
    getExpectedConfig as traitGetExpectedConfig;
    getOverriddenConfig as traitGetOverriddenConfig;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_overlay'];

  /**
   * A list of collections for this test's configuration.
   *
   * @var string[]
   */
  protected array $collections = [StorageInterface::DEFAULT_COLLECTION];

  /**
   * The site UUID for the test site.
   *
   * @var string
   */
  protected string $siteUuid;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    vfsStream::setup('empty');

    $profile = $this->profile;
    $this->profile = FALSE;
    parent::prepareEnvironment();
    $this->profile = $profile;

    $this->siteUuid = (new Php())->generate();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation() {
    /* @see \Drupal\Tests\config_overlay\Functional\ExistingConfig\ExistingConfigTestBase::prepareEnvironment() */
    return 'vfs://empty';
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
   * Returns the "system.date" configuration data for this test.
   *
   * @return array{'first_day': int, 'country': array, 'timezone': array}
   *   The extension configuration data.
   */
  protected function getSystemDateConfiguration(): array {
    $config = [
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
    ];

    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      // See https://www.drupal.org/project/drupal/issues/3437325
      $config['country']['default'] = '';
    }

    return $config;
  }

  /**
   * Returns the "system.mail" configuration data for this test.
   *
   * @return array{'interface': string[], 'mailer_dsn'?: array}
   *   The extension configuration data.
   */
  protected function getSystemMailConfiguration(): array {
    $config = [
      'interface' => [
        'default' => 'test_mail_collector',
      ],
    ];

    if (version_compare(\Drupal::VERSION, '10.2.0-dev', '>=')) {
      $config['mailer_dsn'] = [
        'scheme' => 'null',
        'host' => 'null',
        'user' => NULL,
        'password' => NULL,
        'port' => NULL,
        'options' => [],
      ];
    }

    return $config;
  }

  /**
   * Returns the "system.site" configuration data for this test.
   *
   * @return array
   *   The extension configuration data.
   */
  protected function getSystemSiteConfiguration(): array {
    $config = [
      'langcode' => 'en',
      'uuid' => $this->siteUuid,
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
    ];

    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      unset($config['mail_notification']);
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigSync(): void {
    $change_list = $this->configImporter()->getStorageComparer()->getChangelist();
    $expected = [
      'create' => [],
      'update' => [],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected, $change_list);
  }

}
