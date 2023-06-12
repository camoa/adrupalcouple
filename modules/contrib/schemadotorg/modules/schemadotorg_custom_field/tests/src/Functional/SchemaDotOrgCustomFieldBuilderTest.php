<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_custom_field\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org action.
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldBuilder
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldBuilderTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'schemadotorg_ui',
    'schemadotorg_custom_field',
  ];

  /**
   * Test Schema.org custom field builder.
   */
  public function testBuilder(): void {
    $assert_session = $this->assertSession();

    // Create a page content type which use layout paragraphs.
    // @todo Determine why the below code is not working as expected.
    // $this->createSchemaEntity('node', 'WebPage');
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Recipe']]);
    $this->submitForm([], 'Save');
    drupal_flush_all_caches();

    // Check node edit form include units.
    // @see \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldBuilder::fieldWidgetFormAlter
    $this->drupalGet('/node/add/recipe');
    $assert_session->responseContains('<span class="field-suffix"> calories</span>');
    $assert_session->responseContains('<span class="field-suffix"> grams</span>');

    // Check node view includes units.
    // @see \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldBuilder::preprocessCustomField
    $node = Node::create([
      'type' => 'recipe',
      'title' => 'Some recipe',
      'schema_nutrition' => [
        [
          'serving_size' => '{service}',
          'calories' => '10.00',
        ],
      ],
    ]);
    $node->save();
    $this->drupalGet($node->toUrl());
    $assert_session->responseContains('<div class="customfield__label">Calories</div>');
    $assert_session->responseContains('<div class="customfield__value">10.00 calories</div>');
  }

}
