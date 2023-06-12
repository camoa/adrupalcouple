<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_role\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org role mapping UI form.
 *
 * @covers \Drupal\schemadotorg_role\SchemaDotOrgRoleManager::mappingDefaultsAlter
 * @covers \Drupal\schemadotorg_role\SchemaDotOrgRoleManager::mappingFormAlter
 * @group schemadotorg
 */
class SchemaDotOrgRoleMappingUiFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_ui', 'schemadotorg_role'];

  /**
   * Test Schema.org role mapping ui form.
   */
  public function testMappingUi(): void {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check that field creation form is replaced with text and edit links.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'PodcastEpisode']]);
    $assert_session->responseContains('<p>The <em class="placeholder">actor</em> property is mapped to the below role-related fields.</p>');
    $assert_session->responseContains('<ul data-drupal-selector="edit-mapping-properties-actor-field-data-fields">');
    $assert_session->responseContains('<li>Hosts (schema_role_host)</li>');
    $assert_session->responseContains('<li>Guests (schema_role_guest)</li>');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/config/search/schemadotorg/settings/properties?destination=' . $base_path . 'admin/structure/types/schemadotorg%3Ftype%3DPodcastEpisode#edit-schemadotorg-role" class="button button--small button--extrasmall" data-drupal-selector="edit-mapping-properties-actor-field-data-edit" id="edit-mapping-properties-actor-field-data-edit">Edit settings</a>');
  }

}
