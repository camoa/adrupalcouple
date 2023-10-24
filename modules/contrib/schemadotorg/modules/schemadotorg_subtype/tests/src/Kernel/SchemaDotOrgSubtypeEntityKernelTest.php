<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests Schema.org subtype entity types.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeEntityKernelTest extends SchemaDotOrgEntityKernelTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'schemadotorg_subtype',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');

    $this->installConfig(['schemadotorg_subtype']);
  }

  /**
   * Tests creating common entity type/bundle Schema.org types.
   */
  public function testCreateSchemaEntity(): void {
    // Check creating node:Event Schema.org mapping with subtype.
    $mapping = $this->createSchemaEntity('node', 'Event');
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('event', $mapping->getTargetBundle());
    $this->assertEquals('Event', $mapping->getSchemaType());
    $this->assertEquals($mapping->getSchemaProperties(), [
      'body' => 'description',
      'langcode' => 'inLanguage',
      'schema_duration' => 'duration',
      'schema_end_date' => 'endDate',
      'schema_start_date' => 'startDate',
      'title' => 'name',
      'schema_event_subtype' => 'subtype',
    ]);

    // Create Thing with mapping.
    $node_type = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $node_type->save();
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
        'schema_thing_subtype' => 'subtype',
      ],
    ]);
    $node_mapping->save();
    $this->createSchemaDotOrgField('node', 'Thing');
    $this->createSchemaDotOrgSubTypeField('node', 'Thing');

    // Check getting the mappings for Schema.org properties with subtype.
    $expected_schema_properties = [
      'title' => 'name',
      'schema_alternate_name' => 'alternateName',
      'schema_thing_subtype' => 'subtype',
    ];
    $this->assertEquals($expected_schema_properties, $node_mapping->getSchemaProperties());

    // Check getting the field name for a subtype property.
    $this->assertEquals('schema_thing_subtype', $node_mapping->getSchemaPropertyFieldName('subtype'));
  }

}
