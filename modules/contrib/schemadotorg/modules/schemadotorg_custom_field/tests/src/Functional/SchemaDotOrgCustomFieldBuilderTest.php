<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_custom_field\Functional;

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

    $this->createSchemaEntity('node', 'Recipe');

    $this->drupalLogin($this->rootUser);

    // Check node edit form include units.
    // @see \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldBuilder::fieldWidgetFormAlter
    $this->drupalGet('/node/add/recipe');
    $assert_session->responseContains('<span class="field-suffix"> calories</span>');
    $assert_session->responseContains('<span class="field-suffix"> grams</span>');

    // Create a recipe node and confirm that calories includes units.
    $edit = [
      'title[0][value]' => 'Some recipe',
      'schema_nutrition[0][calories]' => '10.00',
    ];
    $this->submitForm($edit, 'Save');

    $assert_session->responseContains('<title>Some recipe | Drupal</title>');
    // @todo Determine why the custom field is not being rendered.
    // $assert_session->responseContains('<div class="customfield__label">Calories</div>');
    // $assert_session->responseContains('<div class="customfield__value">10.00 calories</div>');
  }

}
