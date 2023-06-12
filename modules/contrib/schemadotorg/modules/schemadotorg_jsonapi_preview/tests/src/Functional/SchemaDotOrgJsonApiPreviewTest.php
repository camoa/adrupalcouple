<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonapi_preview\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org JSON:API preview.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonApiPreviewTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_jsonapi', 'schemadotorg_jsonapi_preview'];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder(): void {
    $assert_session = $this->assertSession();

    $account = $this->createUser(['access content', 'view schemadotorg jsonapi']);

    // Create Thing content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'thing']);

    // @todo Determine why JSON:API extras requires a cache clear.
    drupal_flush_all_caches();

    $node = $this->drupalCreateNode([
      'type' => 'thing',
      'title' => 'Something',
    ]);
    $node->save();

    // Check that JSON:API preview is not displayed for users without permission.
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON:API');

    // Check that JSON:API preview is not displayed.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseContains('Schema.org JSON:API');

    // Update JSON:API preview configuration to not be displayed on the node.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/config/search/schemadotorg/settings/jsonapi');
    $edit = ['schemadotorg_jsonapi_preview[visibility][request_path][pages]' => '/node/' . $node->id()];
    $this->submitForm($edit, 'Save configuration');

    // Check the JSON:API preview can be hidden.
    $this->drupalLogin($account);
    $this->drupalGet($node->toUrl());
    $assert_session->responseNotContains('Schema.org JSON:API');
  }

}
