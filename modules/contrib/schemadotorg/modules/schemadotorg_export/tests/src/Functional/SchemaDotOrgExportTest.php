<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_export\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org export.
 *
 * @group schemadotorg
 */
class SchemaDotOrgExportTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'field_ui',
    'schemadotorg_ui',
    'schemadotorg_mapping_set',
    'schemadotorg_subtype',
    'schemadotorg_report',
    'schemadotorg_export',
  ];

  /**
   * Test Schema.org descriptions.
   */
  public function testDescriptions(): void {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer schemadotorg',
    ]);
    $this->drupalLogin($account);

    // Create the 'Thing' content type with type and alternateName fields.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $edit = [
      'mapping[properties][subtype][field][name]' => TRUE,
      'mapping[properties][alternateName][field][name]' => '_add_',
      'mapping[properties][name][field][name]' => '_add_',
    ];
    $this->submitForm($edit, 'Save');

    // Check that 'Download CSV' link is added to the Schema.org mapping list.
    $this->drupalGet('/admin/config/search/schemadotorg');
    $assert_session->responseContains('<u>⇩</u> Download CSV');

    // Check Schema.org mapping CSV export.
    $this->drupalGet('/admin/config/search/schemadotorg/export');
    $assert_session->responseContains('entity_type,bundle,schema_type,schema_subtyping,schema_properties');
    $assert_session->responseContains('node,thing,Thing,Yes,"subtype; alternateName; name"');

    // Check Schema.org mapping set overview CSV export.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/export');
    $assert_session->responseContains('title,name,types');
    $assert_session->responseContains('Required,required,"media:AudioObject; media:DataDownload; media:ImageObject; media:VideoObject; taxonomy_term:DefinedTerm; node:Person"');

    // Check Schema.org mapping set details CSV export.
    $this->drupalGet('/admin/config/search/schemadotorg/sets/required/export');
    $assert_session->responseContains('schema_type,entity_type,entity_bundle,field_label,field_description,schema_property,field_name,existing_field,field_type,unlimited_field');
    $assert_session->responseContains('Person,node,person,"Middle name","An additional name for a Person, can be used for a middle name.",additionalName,schema__additional_name,No,string,No');

    // Check Schema.org type CSV export.
    $this->drupalGet('/admin/reports/schemadotorg/Article/export');
    $assert_session->responseContains('id,label,comment,sub_property_of,equivalent_property,subproperties,domain_includes,range_includes,inverse_of,supersedes,superseded_by,is_part_of,drupal_name,drupal_label,status');
    $assert_session->responseContains('https://schema.org/author,author,"The author of this content or rating. Please note that author is special in that HTML 5 provides a special mechanism for indicating authorship via the rel tag. That is equivalent to this and may be used interchangeably.",,,,"https://schema.org/CreativeWork, https://schema.org/Rating","https://schema.org/Organization, https://schema.org/Person",,,,,author,Author');
  }

}
