<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_starterkti\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Starter kit controller.
 *
 * @group schemadotorg
 */
class SchemaDotOrgStarterKitControllerTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_starterkit'];

  /**
   * Test Schema.org starterkit controller.
   */
  public function testController(): void {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->drupalCreateUser(['administer schemadotorg']));

    // Check missing starterkit missing a dependency.
    $this->drupalGet('/admin/config/search/schemadotorg/starterkits');
    $assert_session->responseContains(' <td><ul><li>missing_dependency <em>(Missing)</em></li></ul></td>');
  }

}
