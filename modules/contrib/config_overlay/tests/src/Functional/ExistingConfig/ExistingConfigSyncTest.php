<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

use Drupal\system\MenuInterface;

/**
 * Tests installation of the Testing profile from existing configuration.
 *
 * @group config_overlay
 */
class ExistingConfigSyncTest extends ExistingConfigSyncTestBase {

  /**
   * {@inheritdoc}
   */
  public function testConfigExport(): void {
    parent::testConfigExport();

    // Test that core ships a footer menu by default. This makes sure that
    // ExistingConfigDeleteShippedSyncTest does not yield a false positive.
    /* @see \Drupal\Tests\config_overlay\Functional\ExistingConfig\ExistingConfigDeleteShippedSyncTest::testConfigExport() */
    $menuStorage = $this->entityTypeManager->getStorage('menu');
    $this->assertInstanceOf(MenuInterface::class, $menuStorage->load('footer'));
    $this->assertFalse($this->config('system.menu.footer')->isNew());
  }

}
