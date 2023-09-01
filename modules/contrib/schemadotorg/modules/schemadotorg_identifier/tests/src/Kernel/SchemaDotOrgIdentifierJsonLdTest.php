<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org identifier JSON-LD.
 *
 * @covers schemadotorg_identifier_schemadotorg_jsonld_schema_type_entity_load()
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierJsonLdTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_jsonld',
    'schemadotorg_identifier',
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
    $this->installConfig(['schemadotorg_identifier', 'schemadotorg_jsonld']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
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

    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->createSchemaEntity('node', 'MedicalBusiness');

    $node = Node::create([
      'type' => 'medical_business',
      'title' => 'Something',
      'schema_identifier_npi' => [
        'value' => '000000000',
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check JSON-LD identifier property.
    $jsonld = $this->builder->buildEntity($node);
    $expected_identifier = [
      [
        '@type' => 'PropertyValue',
        'propertyID' => 'npi',
        'value' => '000000000',
      ],
      [
        '@type' => 'PropertyValue',
        'propertyID' => 'uuid',
        'value' => $node->uuid(),
      ],
    ];
    $this->assertEquals($expected_identifier, $jsonld['identifier']);
  }

}
