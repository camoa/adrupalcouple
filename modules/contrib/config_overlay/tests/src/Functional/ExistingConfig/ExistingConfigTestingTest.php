<?php

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Core\Config\StorageInterface;

/**
 * Tests installation with existing configuration with Config Overlay.
 *
 * @group config_overlay
 */
class ExistingConfigTestingTest extends ExistingConfigTestBase {

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
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.date']['timezone']['default'] = 'UTC';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['name'] = 'Site with Testing profile and Config Overlay';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['mail'] = 'admin@example.com';

    return $overridden_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseModules(): array {
    $modules = parent::getBaseModules();
    $modules += [
      'dynamic_page_cache' => 0,
      'page_cache' => 0,
    ];
    return $modules;
  }

}
