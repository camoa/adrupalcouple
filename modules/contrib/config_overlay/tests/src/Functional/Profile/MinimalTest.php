<?php

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests installation of the Minimal profile with Config Overlay.
 *
 * @group config_overlay
 */
class MinimalTest extends ConfigOverlayTestBase {

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

    /* @see node_install() */
    $role_ids = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];
    foreach ($role_ids as $role_id) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["user.role.$role_id"] = [
        'dependencies' => [
          'module' => [
            'system',
          ],
        ],
        'permissions' => [
          'access content',
        ],
      ];
    }

    return $overridden_config;
  }

}
