<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\Component\Serialization\Yaml;

/**
 * Tests installation from existing configuration with deleted configuration.
 *
 * @group config_overlay
 */
class ExistingConfigDeleteShippedSyncTest extends ExistingConfigSyncTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    $profileDirectory = "$this->siteDirectory/profiles/$this->profile";
    file_put_contents(
      "$profileDirectory/config/install/config_overlay.deleted.yml",
      Yaml::encode([
        'names' => [
          'system.menu.footer',
        ],
      ]),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigExport(): void {
    parent::testConfigExport();

    $menuStorage = $this->entityTypeManager->getStorage('menu');
    $this->assertNull($menuStorage->load('footer'));
    $this->assertTrue($this->config('system.menu.footer')->isNew());
  }

}
