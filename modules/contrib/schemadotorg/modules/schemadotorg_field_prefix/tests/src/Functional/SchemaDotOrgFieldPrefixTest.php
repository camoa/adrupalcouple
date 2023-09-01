<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_field_prefix\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org field prefix.
 *
 * @covers schemadotorg_field_prefix_form_field_ui_field_storage_add_form_alter()
 * @group schemadotorg
 */
class SchemaDotOrgFieldPrefixTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'field_ui',
    'schemadotorg_field_prefix',
  ];

  /**
   * Test Schema.org field prefix.
   */
  public function testFieldPrefix(): void {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->rootUser);

    // Create the page content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Check that changing the field prefix does exist.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $assert_session->fieldExists('field_prefix');
    // Check the field prefix options.
    $assert_session->responseContains('<option value="field_" selected="selected">field_</option><option value="field_page_">field_page_</option><option value="schema_">schema_</option><option value="schema_page_">schema_page_</option><option value="">&lt;none&gt;</option>');
    // Check the field prefix descripion.
    $assert_session->responseContains("Select the field's prefix. Use <code>&lt;none&gt;</code> with caution because the machine-readable name can conflict with existing base field/property names.");

    // Check missing label validation.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'label' => '',
      'field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('Add new field: you need to provide a label.');

    // Check missing field name validation.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'label' => 'Test',
      'field_name' => '',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('Add new field: you need to provide a machine name for the field.');

    // Check create a schema_* field.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'label' => 'Test',
      'field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'schema_test'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'page', 'schema_test'));

    // Check existing schema_* field validation.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $edit = [
      'new_storage_type' => 'string',
      'field_prefix' => 'schema_',
      'label' => 'Test',
      'field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('There was a problem creating field <em class="placeholder">Test</em>: &#039;field_storage_config&#039; entity with ID &#039;node.schema_test&#039; already exists.');

    // Check that clearing the field option remove the  field prefix select menu.
    $this->config('schemadotorg_field_prefix.settings')
      ->set('field_prefix_options', [])
      ->save();
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $assert_session->fieldNotExists('field_prefix');
  }

}
