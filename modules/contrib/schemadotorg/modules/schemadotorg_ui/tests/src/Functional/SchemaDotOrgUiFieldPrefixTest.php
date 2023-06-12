<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org field prefix.
 *
 * @covers schemadotorg_ui_form_field_ui_field_storage_add_form_alter()
 * @group schemadotorg
 */
class SchemaDotOrgUiFieldPrefixTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_ui',
  ];

  /**
   * Test Schema.org field prefix.
   */
  public function testMappingForm(): void {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->rootUser);

    // Create the page content type.
    $this->drupalCreateContentType(['type' => 'page']);

    // Check that changing the field prefix does not exist.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $assert_session->fieldNotExists('field_prefix');

    $this->config('schemadotorg.settings')
      ->set('field_prefix_ui', TRUE)
      ->save();

    // Check that changing the field prefix does exist.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $assert_session->fieldExists('field_prefix');

    // Check missing label validation.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'schemadotorg_label' => '',
      'schemadotorg_field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('Add new field: you need to provide a label.');

    // Check missing field name validation.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'schemadotorg_label' => 'Test',
      'schemadotorg_field_name' => '',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('Add new field: you need to provide a machine name for the field.');

    // Check create a schema_* field.
    $edit = [
      'new_storage_type' => 'text',
      'field_prefix' => 'schema_',
      'schemadotorg_label' => 'Test',
      'schemadotorg_field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'schema_test'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'page', 'schema_test'));

    // Check existing schema_* field validation.
    $this->drupalGet('/admin/structure/types/manage/page/fields/add-field');
    $edit = [
      'new_storage_type' => 'string',
      'field_prefix' => 'schema_',
      'schemadotorg_label' => 'Test',
      'schemadotorg_field_name' => 'test',
    ];
    $this->submitForm($edit, 'Save and continue');
    $assert_session->responseContains('There was a problem creating field <em class="placeholder"></em>: &#039;field_storage_config&#039; entity with ID &#039;node.schema_test&#039; already exists.');
  }

}
