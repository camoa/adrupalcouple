<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_diagram\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Diagram settings form.
 *
 * @covers \Drupal\schemadotorg_diagram\Form\SchemaDotOrgSubtypeSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgDiagramSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_diagram'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Diagram settings form.
   */
  public function testSettingsForm(): void {
    $this->assertSaveSettingsConfigForm('schemadotorg_diagram.settings', '/admin/config/search/schemadotorg/settings/general');
  }

}
