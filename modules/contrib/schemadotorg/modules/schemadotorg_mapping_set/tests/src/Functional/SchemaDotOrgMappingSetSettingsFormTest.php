<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_mapping_set\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org mapping set settings form.
 *
 * @covers \Drupal\schemadotorg_mapping_set\Form\SchemaDotOrgMappingSetSettingsForm
 * @group schemadotorg
 */
class SchemaDotOrgMappingSetSettingsFormTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'paragraphs',
    'taxonomy',
    'block_content',
    'schemadotorg_mapping_set',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);
  }

  /**
   * Test Schema.org Mapping Set settings form.
   */
  public function testSettingsForm(): void {
    $assert_session = $this->assertSession();

    // Check saving config form.
    $this->assertSaveSettingsConfigForm('schemadotorg_mapping_set.settings', '/admin/config/search/schemadotorg/sets/settings');

    // Check type validation.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/settings');
    $this->submitForm(['sets' => 'test|Test|test'], 'Save configuration');
    $assert_session->responseContains('<em class="placeholder">test</em> in <em class="placeholder">Test</em> is not valid. Please enter the entity type id and Schema.org type (i.e. entity_type_id:SchemaType).');

    // Check entity type id validation.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/settings');
    $this->submitForm(['sets' => 'test|Test|test:Test'], 'Save configuration');
    $assert_session->responseContains('<em class="placeholder">test</em> in <em class="placeholder">Test</em> is not valid entity type.');

    // Check Schema.org type validation.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/settings');
    $this->submitForm(['sets' => 'test|Test|node:Test'], 'Save configuration');
    $assert_session->responseContains('<em class="placeholder">Test</em> in <em class="placeholder">Test</em> is not valid Schema.org type.');

    // Check node:Thing is valid.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/settings');
    $this->submitForm(['sets' => 'test|Test|node:Thing'], 'Save configuration');
    $assert_session->responseContains('The configuration options have been saved.');
  }

}
