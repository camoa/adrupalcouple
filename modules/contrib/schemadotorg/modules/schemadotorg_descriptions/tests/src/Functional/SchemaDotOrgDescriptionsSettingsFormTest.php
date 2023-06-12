<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_descriptions\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Descriptions settings form.
 *
 * @covers \Drupal\schemadotorg_descriptions\Form\SchemaDotOrgSubtypeSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgDescriptionsSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_descriptions'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Descriptions settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_descriptions.settings', '/admin/config/search/schemadotorg/settings/general');
  }

}
