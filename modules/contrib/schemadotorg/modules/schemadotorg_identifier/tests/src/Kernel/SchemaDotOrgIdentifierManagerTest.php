<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org identifier field.
 *
 * @covers \Drupal\schemadotorg_identifier\SchemaDotOrgIdentifierManager
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'field_group',
    'schemadotorg_field_group',
    'schemadotorg_identifier',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org identifier manager.
   *
   * @var \Drupal\schemadotorg_identifier\SchemaDotOrgIdentifierManagerInterface
   */
  protected $identifierManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'schemadotorg_field_group',
      'schemadotorg_identifier',
    ]);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');

    $this->identifierManager = $this->container->get('schemadotorg_identifier.manager');
  }

  /**
   * Test Schema.org identifier.
   */
  public function testIdentifier(): void {
    // Add Drupal's UUID as an identifier.
    $this->config('schemadotorg_identifier.settings')
      ->set('field_definitions.uuid', [])
      ->set('schema_types.Thing', ['uuid'])
      ->save();

    $this->createSchemaEntity('node', 'MedicalBusiness');

    /* ********************************************************************** */

    // Check that the identifier fields are created when a mapping is inserted.
    $this->assertNull(FieldConfig::loadByName('node', 'medical_business', 'uuid'));
    $this->assertNull(FieldConfig::loadByName('node', 'medical_business', 'schema_identifier_uuid'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'medical_business', 'schema_identifier_npi'));

    // Check that the identifier field group is created via the form display.
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'medical_business', 'default');
    $component = $form_display->getComponent('schema_identifier_npi');
    $this->assertEquals('string_textfield', $component['type']);
    $field_group = $form_display->getThirdPartySettings('field_group');
    $this->assertEquals(['schema_identifier_npi'], $field_group['group_identifiers']['children']);
    $this->assertEquals('Identifiers', $field_group['group_identifiers']['label']);
    $this->assertEquals('details', $field_group['group_identifiers']['format_type']);

    // Check that the identifier field group is created via the view display.
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'medical_business', 'default');
    $component = $view_display->getComponent('schema_identifier_npi');
    $this->assertEquals('string', $component['type']);
    $field_group = $view_display->getThirdPartySettings('field_group');
    $this->assertEquals(['schema_identifier_npi'], $field_group['group_identifiers']['children']);
    $this->assertEquals('Identifiers', $field_group['group_identifiers']['label']);
    $this->assertEquals('fieldset', $field_group['group_identifiers']['format_type']);

    // Check identifier field definitions for a Schema.org mapping.
    $mapping = SchemaDotOrgMapping::load('node.medical_business');
    $expected_field_definitions = [
      'npi' => [
        'property_id' => 'npi',
        'label' => 'National Provider Identifier (NPI)',
        'description' => 'A unique identification number for covered health care providers.',
        'max_length' => 10,
        'field_name' => 'schema_identifier_npi',
        'base_field' => FALSE,
      ],
      'uuid' => [
        'field_name' => 'uuid',
        'property_id' => 'uuid',
        'base_field' => TRUE,
      ],
    ];
    $this->assertEquals(
      $expected_field_definitions,
      $this->identifierManager->getMappingFieldDefinitions($mapping)
    );
  }

}
