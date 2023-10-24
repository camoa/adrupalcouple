<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_epp\Functional;

use Drupal\field\Entity\FieldConfig;
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
   * Disabled config schema checking until the epp.module has fixed its schema.
   *
   * @see https://www.drupal.org/project/epp/issues/3348759
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_epp'];

  /**
   * Test Schema.org Entity Prepopulate node links.
   */
  public function testNodeLinks(): void {
    $assert_session = $this->assertSession();

    $this->appendSchemaTypeDefaultProperties('Organization', ['member', 'subOrganization', 'parentOrganization']);
    $this->appendSchemaTypeDefaultProperties('LocalBusiness', ['-member', 'employee']);
    $this->config('schemadotorg.settings')
      ->set('schema_properties.default_fields.worksFor.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.memberOf.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.subOrganization.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.parentOrganization.type', 'field_ui:entity_reference:node')
      ->save();

    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');
    $this->createSchemaEntity('node', 'LocalBusiness');

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Exclude LocalBusiness from Person.memberOf entity reference.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::load('node.person.schema_member_of');
    $handler_settings = $field_config->getSetting('handler_settings');
    $handler_settings['excluded_schema_types'] = ['LocalBusiness' => 'LocalBusiness'];
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();

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
    $this->drupalGet('/admin/config/schemadotorg/settings/properties');
    $this->submitForm(['schemadotorg_epp[node_links_dropdown]' => FALSE], 'Save configuration');

    // Check that node links are NOT displayed as dropdown.
    $this->drupalGet($organization_node->toUrl()->toString());
    $assert_session->responseNotContains('<div class="schemadotorg-epp-node-links-dropdown">');
    $assert_session->responseContains('<ul class="links inline">');
  }

}
