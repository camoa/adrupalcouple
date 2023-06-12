<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests the Schema.org mapping entity.
 *
 * @coversClass \Drupal\schemadotorg\Entity\SchemaDotOrgMapping
 * @group schemadotorg
 */
class SchemaDotOrgMappingEntityTest extends SchemaDotOrgKernelTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'options',
    'schemadotorg_subtype',
  ];

  /**
   * A node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * A Schema.org mapping entity for a node.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $nodeMapping;

  /**
   * A Schema.org mapping entity for a user.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $userMapping;

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    // Create Thing node with field.
    $node_type = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $node_type->save();
    $this->nodeType = $node_type;
    $this->createSchemaDotOrgField('node', 'Thing');
    $this->createSchemaDotOrgSubTypeField('node', 'Thing');

    // Create Thing with mapping.
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'subtype' => TRUE,
      'schema_properties' => [
        'title' => 'name',
        'schema_alternate_name' => 'alternateName',
        'schema_thing_subtype' => 'subtype',
      ],
    ]);
    $node_mapping->save();
    $this->nodeMapping = $node_mapping;

    // Create user with Person mapping.
    $user_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'user',
      'target_bundle' => 'user',
      'schema_type' => 'Person',
      'subtype' => FALSE,
      'schema_properties' => [
        'name' => 'name',
      ],
    ]);
    $user_mapping->save();
    $this->userMapping = $user_mapping;

    // Set Schema.org mapping storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
  }

  /**
   * Test Schema.org mapping entity.
   */
  public function testSchemaDotOrgMappingEntity(): void {
    $node_mapping = $this->nodeMapping;
    $user_mapping = $this->userMapping;

    // Check getting the entity type for which this mapping is used. (i.e. node)
    $this->assertEquals('node', $node_mapping->getTargetEntityTypeId());

    // Check getting the bundle to be mapped. (i.e. page)
    $this->assertEquals('thing', $node_mapping->getTargetBundle());

    // Check setting the bundle to be mapped.
    $node_mapping->setTargetBundle('cat');
    $this->assertEquals('cat', $node_mapping->getTargetBundle());
    $node_mapping->setTargetBundle('thing');

    // Check getting the entity type definition. (i.e. node annotation)
    $target_entity_type_definition = $node_mapping->getTargetEntityTypeDefinition();
    $this->assertInstanceOf(ContentEntityType::class, $target_entity_type_definition);
    $this->assertEquals('node', $target_entity_type_definition->id());
    $this->assertEquals('Content', $target_entity_type_definition->getLabel());

    // Check getting the entity type's bundle ID. (i.e. node_type)
    $this->assertEquals('node_type', $node_mapping->getTargetEntityTypeBundleId());

    // Check getting the entity type's bundle definition. (i.e. node_type annotation)
    $target_entity_type_bundle_definition = $node_mapping->getTargetEntityTypeBundleDefinition();
    $this->assertInstanceOf(ConfigEntityType::class, $target_entity_type_bundle_definition);
    $this->assertEquals('node_type', $target_entity_type_bundle_definition->id());
    $this->assertEquals('Content type', $target_entity_type_bundle_definition->getLabel());

    // Check getting the bundle entity type. (i.e. node_type:page)
    $target_entity_bundle_entity = $node_mapping->getTargetEntityBundleEntity();
    $this->assertInstanceOf(ConfigEntityType::class, $target_entity_type_bundle_definition);
    $this->assertEquals('thing', $target_entity_bundle_entity->id());
    $this->assertEquals('Thing', $target_entity_bundle_entity->label());

    // Check determining if the entity type supports bundling.
    $this->assertTrue($node_mapping->isTargetEntityTypeBundle());
    $this->assertFalse($user_mapping->isTargetEntityTypeBundle());

    // Check determining if a new bundle entity is being created.
    $this->assertFalse($node_mapping->isNewTargetEntityTypeBundle());
    $this->assertFalse($user_mapping->isNewTargetEntityTypeBundle());
    $new_bundle_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'place',
      'schema_type' => 'Place',
    ]);
    $this->assertTrue($new_bundle_mapping->isNewTargetEntityTypeBundle());

    // Check getting the Schema.org type to be mapped.
    $this->assertEquals('Thing', $node_mapping->getSchemaType());

    // Check setting the Schema.org type to be mapped.
    $node_mapping->setSchemaType('Cat');
    $this->assertEquals('Cat', $node_mapping->getSchemaType());
    $node_mapping->setSchemaType('Thing');

    // Check getting the mappings for Schema.org properties.
    $expected_schema_properties = [
      'title' => 'name',
      'schema_alternate_name' => 'alternateName',
      'schema_thing_subtype' => 'subtype',
    ];
    $this->assertEquals($expected_schema_properties, $node_mapping->getSchemaProperties());

    // Check getting the original mappings for Schema.org properties.
    $this->assertEquals($node_mapping->getOriginalSchemaProperties(), $node_mapping->getSchemaProperties());

    // Check setting the original mappings for Schema.org properties.
    $node_mapping->setOriginalSchemaProperties([]);
    $this->assertEquals([], $node_mapping->getOriginalSchemaProperties());

    // Check getting the new mappings for Schema.org properties.
    $this->assertEquals($node_mapping->getSchemaProperties(), $node_mapping->getNewSchemaProperties());
    $node_mapping->save();
    $node_mapping->setSchemaPropertyMapping('body', 'description');
    $this->assertEquals(['body' => 'description'], $node_mapping->getNewSchemaProperties());

    // Check getting the mapping set for a property.
    $this->assertEquals('name', $node_mapping->getSchemaPropertyMapping('title'));

    // Check setting the mapping for a Schema.org property.
    $node_mapping->setSchemaPropertyMapping('created', 'dateCreated');
    $this->assertEquals('dateCreated', $node_mapping->getSchemaPropertyMapping('created'));

    // Check removing the Schema.org property mapping.
    $node_mapping->removeSchemaProperty('created');
    $this->assertNull($node_mapping->getSchemaPropertyMapping('created'));

    // Check getting the field name for a property.
    $this->assertNull($node_mapping->getSchemaPropertyFieldName('notAProperty'));
    $this->assertEquals('title', $node_mapping->getSchemaPropertyFieldName('name'));
    $this->assertEquals('schema_alternate_name', $node_mapping->getSchemaPropertyFieldName('alternateName'));
    $this->assertEquals('schema_thing_subtype', $node_mapping->getSchemaPropertyFieldName('subtype'));

    // Check calculating and getting the configuration dependencies.
    $expected_dependencies = [
      'config' => [
        'field.field.node.thing.schema_alternate_name',
        'field.field.node.thing.schema_thing_subtype',
        'node.type.thing',
      ],
    ];
    $actual_dependencies = $node_mapping->calculateDependencies()->getDependencies();
    $this->assertEquals($expected_dependencies, $actual_dependencies);

    // Check deleting field removes the property mapping.
    $this->assertEquals('alternateName', $node_mapping->getSchemaPropertyMapping('schema_alternate_name'));
    FieldConfig::load('node.thing.schema_alternate_name')->delete();
    $this->storage->resetCache();
    $node_mapping = $this->storage->load('node.thing');
    $this->assertNull($node_mapping->getSchemaPropertyMapping('schema_alter_name'));

    // Check deleting the target type removes the mapping.
    // @see \Drupal\schemadotorg\Entity\SchemaDotOrgMapping::onDependencyRemoval
    $this->assertNotNull($this->storage->load('node.thing'));
    $this->nodeType->delete();
    $this->storage->resetCache();
    $this->assertNull($this->storage->load('node.thing'));
  }

}
