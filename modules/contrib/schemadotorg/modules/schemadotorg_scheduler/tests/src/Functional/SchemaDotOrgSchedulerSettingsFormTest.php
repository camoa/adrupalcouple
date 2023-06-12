<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_scheduler\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org scheduler settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSchedulerSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_scheduler'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Scheduler settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_scheduler.settings', '/admin/config/search/schemadotorg/settings/types');
  }

}
