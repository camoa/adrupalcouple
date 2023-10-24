<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_epp\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org Entity Prepopulate.
 *
 * @group schemadotorg
 */
class SchemaDotOrgEntityPrepopulateKernelTest extends SchemaDotOrgEntityKernelTestBase {

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
  protected static $modules = [
    'epp',
    'schemadotorg_epp',
  ];

  /**
   * The Schema.org mapping manager.
   */
  protected SchemaDotOrgMappingManagerInterface $mappingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(self::$modules);
  }

  /**
   * Test Schema.org entity prepopulate.
   */
  public function testEntityPrepopulate(): void {
    $this->appendSchemaTypeDefaultProperties('Organization', ['subOrganization', 'parentOrganization', 'subjectOf']);

    $this->config('schemadotorg.settings')
      ->set('schema_properties.default_fields.memberOf.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.worksFor.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.parentOrganization.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.subOrganization.type', 'field_ui:entity_reference:node')
      ->set('schema_properties.default_fields.subjectOf.type', 'field_ui:entity_reference:node')
      ->save();

    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');

    /* ********************************************************************** */

    $fields = [
      'node.person.schema_member_of' => 'target_id: [current-page:query:member_of]',
      'node.person.schema_works_for' => 'target_id: [current-page:query:works_for]',
      'node.organization.schema_parent_organization' => 'target_id: [current-page:query:parent_organization]',
      'node.organization.schema_sub_organization' => 'target_id: [current-page:query:sub_organization]',
      'node.organization.schema_subject_of' => 'target_id: [current-page:query:subject_of]',
    ];
    foreach ($fields as $id => $value) {
      $this->assertEquals($value, FieldConfig::load($id)->getThirdPartySetting('epp', 'value'));
    }
  }

}
