<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Subtype JSON-LD.
 *
 * @covers schemadotorg_subtype_schemadotorg_jsonld_schema_type_entity_alter()
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeJsonLdTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_jsonld',
    'schemadotorg_subtype',
  ];

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld', 'schemadotorg_subtype']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org Subtype JSON-LD.
   */
  public function testSubtypeJsonLd(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    /** @var \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface $schema_config_manager */
    $schema_config_manager = \Drupal::service('schemadotorg.config_manager');
    $schema_config_manager->setSchemaTypeDefaultProperties('Person', 'additionalType');

    // Add Caregiver to Person allowed type.
    $this->config('schemadotorg_subtype.settings')
      ->set('default_subtypes', ['Person'])
      ->set('default_allowed_values.Person', ['Patient' => 'Patient', 'Caregiver' => 'Caregiver'])
      ->save();

    $this->createSchemaEntity('node', 'Person');

    $patient_node = Node::create([
      'type' => 'person',
      'title' => 'Patient',
      'schema_person_subtype' => 'Patient',
    ]);
    $patient_node->save();

    $caregiver_node = Node::create([
      'type' => 'person',
      'title' => 'Caregiver',
      'schema_person_subtype' => 'Caregiver',
    ]);
    $caregiver_node->save();

    // Check that Patient subtype sets the @type to Patient.
    $expected_result = [
      '@type' => 'Patient',
      '@url' => $patient_node->toUrl()->setAbsolute()->toString(),
      'name' => 'Patient',
    ];
    $this->assertEquals($expected_result, $this->builder->buildEntity($patient_node));

    // Check that Caregiver subtype sets the 'additionalType' property
    // to Caregiver.
    $expected_result = [
      '@type' => 'Person',
      '@url' => $caregiver_node->toUrl()->setAbsolute()->toString(),
      'name' => 'Caregiver',
      'additionalType' => 'Caregiver',
    ];
    $this->assertEquals($expected_result, $this->builder->buildEntity($caregiver_node));

    // Add 'Parent' as the additional type.
    $caregiver_node->schema_additional_type->value = 'Parent';
    $caregiver_node->save();

    // Check that Caregiver subtype is merged with the 'Parent'
    // additionalType property.
    $expected_result = [
      '@type' => 'Person',
      '@url' => $caregiver_node->toUrl()->setAbsolute()->toString(),
      'name' => 'Caregiver',
      'additionalType' => [
        'Parent',
        'Caregiver',
      ],
    ];
    $this->assertEquals($expected_result, $this->builder->buildEntity($caregiver_node));
  }

}
