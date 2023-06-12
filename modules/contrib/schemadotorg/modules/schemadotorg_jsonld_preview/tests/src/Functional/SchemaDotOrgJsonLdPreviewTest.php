<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld_preview\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD preview.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdPreviewTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_jsonld', 'schemadotorg_jsonld_preview'];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder(): void {
    $assert_session = $this->assertSession();

    $account = $this->createUser(['access content', 'view schemadotorg jsonld']);

    // Create Thing content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'thing']);
    $node = $this->drupalCreateNode([
      'type' => 'thing',
      'title' => 'Something',
    ]);
    $node->save();

    // Check that JSON-LD preview is not displayed for users without permission.
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Check that JSON-LD preview is not displayed without a mapping.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Create a Schema.org mapping for Thing.
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
      ],
    ])->save();

    // Check that JSON-LD preview is not displayed for users without permission.
    $this->drupalLogout();
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');

    // Check that JSON-LD preview is now displayed.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseContains('Schema.org JSON-LD');

    // Update JSON-LD preview configuration to not be displayed on the node.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/config/search/schemadotorg/settings/jsonld');
    $edit = ['schemadotorg_jsonld_preview[visibility][request_path][pages]' => '/node/' . $node->id()];
    $this->submitForm($edit, 'Save configuration');

    // Check the JSON-LD preview can be hidden.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON-LD');
  }

}
