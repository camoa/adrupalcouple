<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_focal_point\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org focal point settings form.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFocalPointSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_focal_point'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Focal Point settings form.
   */
  public function testSettingsForm(): void {
    $session = $this->assertSession();

    $this->assertSaveSettingsConfigForm('schemadotorg_focal_point.settings', '/admin/config/schemadotorg/settings/properties');

    // Get the Schema.org types settings form.
    $this->drupalGet('/admin/config/schemadotorg/settings/properties');

    // Check validating that width and height are set.
    $edit = [
      'schemadotorg_focal_point[image_styles]' => '4x3:
  ratio: 4:3
  max-width:',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->responseContains('A max-width is required for the <em class="placeholder">4x3</em> image style.');

    // Check validating that width and height are integers.
    $edit = [
      'schemadotorg_focal_point[image_styles]' => '4x3:
  ratio: 4:3
  max-width: A',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->responseContains('Image styles field is invalid.');
  }

}
