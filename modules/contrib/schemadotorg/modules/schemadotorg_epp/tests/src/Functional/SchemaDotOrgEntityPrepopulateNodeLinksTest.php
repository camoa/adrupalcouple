<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_epp\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Entity Prepopulate node links.
 *
 * @covers schemadotorg_epp_node_links_alter()
 * @group schemadotorg
 */
class SchemaDotOrgEntityPrepopulateNodeLinksTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the cer.module has fixed its schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'schemadotorg_cer',
    'schemadotorg_epp',
  ];

  /**
   * Test Schema.org Entity Prepopulate node links.
   */
  public function testNodeLinks(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    $this->appendSchemaTypeDefaultProperties('Organization', ['member']);
    $this->appendSchemaTypeDefaultProperties('LocalBusiness', ['-member', 'employee']);

    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');
    $this->createSchemaEntity('node', 'LocalBusiness');

    $organization_node = $this->drupalCreateNode(['type' => 'organization']);
    $local_business_node = $this->drupalCreateNode(['type' => 'local_business']);

    // Check organization node links.
    $this->drupalGet($organization_node->toUrl()->toString());
    $assert_session->linkExists('Add Person');
    $assert_session->linkByHrefExists('/node/add/person?member_of=' . $organization_node->id());
    $assert_session->linkByHrefNotExists('/node/add/person?works_for=' . $organization_node->id());
    $assert_session->linkExists('Add Local Business');
    $assert_session->linkByHrefExists('/node/add/local_business?parent_organization=' . $organization_node->id());
    $assert_session->linkExists('Add Organization');
    $assert_session->linkByHrefExists('/node/add/organization?parent_organization=' . $organization_node->id());

    // Check local business node links.
    $this->drupalGet($local_business_node->toUrl()->toString());
    $assert_session->linkExists('Add Person');
    $assert_session->linkByHrefNotExists('/node/add/person?member_of=' . $local_business_node->id());
    $assert_session->linkByHrefExists('/node/add/person?works_for=' . $local_business_node->id());
    $assert_session->linkExists('Add Local Business');
    $assert_session->linkByHrefExists('/node/add/local_business?parent_organization=' . $local_business_node->id());
    $assert_session->linkExists('Add Organization');
    $assert_session->linkByHrefExists('/node/add/organization?parent_organization=' . $local_business_node->id());

    // Check that node links are displayed as dropdown.
    $this->drupalGet($organization_node->toUrl()->toString());
    $assert_session->responseContains('<div class="schemadotorg-epp-node-links-dropdown">');
    $assert_session->responseNotContains('<ul class="links inline">');

    // Check node links dropdown via the UI to trigger cache clear.
    // @see schemadotorg_epp_schemadotorg_properties_settings_submit()
    $this->drupalGet('/admin/config/search/schemadotorg/settings/properties');
    $this->submitForm(['schemadotorg_epp[node_links_dropdown]' => FALSE], 'Save configuration');

    // Check that node links are NOT displayed as dropdown.
    $this->drupalGet($organization_node->toUrl()->toString());
    $assert_session->responseNotContains('<div class="schemadotorg-epp-node-links-dropdown">');
    $assert_session->responseContains('<ul class="links inline">');
  }

}
