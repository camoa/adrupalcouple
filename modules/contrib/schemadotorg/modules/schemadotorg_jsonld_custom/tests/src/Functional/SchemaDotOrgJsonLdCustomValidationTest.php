<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld_custom\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD custom validation.
 *
 * @covers \Drupal\schemadotorg_jsonapi\Form\SchemaDotOrgDemoSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdCustomValidationTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'schemadotorg_ui',
    'schemadotorg_jsonld_custom',
  ];

  /**
   * Test Schema.org JSON-LD settings form.
   */
  public function testValidation(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check validation of associative array setting's JSON.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/jsonld');
    $this->submitForm(['schemadotorg_jsonld_custom[default_schema_type_json]' => 'xxx|yyy'], 'Save configuration');
    $assert_session->responseContains('The JSON is not valid for <em class="placeholder">xxx</em>. <em class="placeholder">Syntax error</em>');

    // Check validation of a mapping's JSON.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Article']]);
    $this->submitForm(['mapping[third_party_settings][schemadotorg_jsonld_custom][json]' => 'xxx'], 'Save');
    $assert_session->responseContains('The JSON is not valid. <em class="placeholder">Syntax error</em>');
  }

}
