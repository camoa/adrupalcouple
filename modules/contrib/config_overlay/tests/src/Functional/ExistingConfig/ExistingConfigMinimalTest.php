<?php

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Core\Config\StorageInterface;

/**
 * Tests installation of the Minimal profile with Config Overlay.
 *
 * @group config_overlay
 */
class ExistingConfigMinimalTest extends ExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

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
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['name'] = 'Site with Minimal profile and Config Overlay';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['mail'] = 'admin@example.com';

    // Note that the anonymous and authenticated roles are not overridden, even
    // though they are changed when installing Node module, as the subsequent
    // configuration import will revert the roles back to the original state.
    /* @see node_install() */

    return $overridden_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseModules(): array {
    $modules = parent::getBaseModules();
    $modules += [
      'block' => 0,
      'dblog' => 0,
      'dynamic_page_cache' => 0,
      'field' => 0,
      'filter' => 0,
      'node' => 0,
      'page_cache' => 0,
      'text' => 0,
    ];
    return $modules;
  }

}
