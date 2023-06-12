<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests the Schema.org mapping storage.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingStorageTest extends SchemaDotOrgKernelTestBase {
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

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Set Schema.org mapping storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');

    // Create page.
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create Thing and Image node with mappings.
    NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ])->save();
    NodeType::create([
      'type' => 'image_object',
      'name' => 'ImageObject',
    ])->save();
    $this->createSchemaDotOrgSubTypeField('node', 'ImageObject');
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
        'image' => 'image',
      ],
    ])->save();
    SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'image_object',
      'schema_type' => 'ImageObject',
      'schema_properties' => [
        'title' => 'name',
      ],
      'subtype' => TRUE,
    ])->save();
  }

  /**
   * Test Schema.org mapping storage.
   */
  public function testSchemaDotOrgMappingStorage(): void {
    $page_node = Node::create(['type' => 'page', 'title' => 'Page']);
    $page_node->save();

    $thing_node = Node::create(['type' => 'thing', 'title' => 'Thing']);
    $thing_node->save();

    $image_node = Node::create(['type' => 'image_object', 'title' => 'Image', 'schema_image_object_subtype' => 'Barcode']);
    $image_node->save();

    // Check determining if an entity is mapped to a Schema.org type.
    $this->assertFalse($this->storage->isEntityMapped($page_node));
    $this->assertTrue($this->storage->isEntityMapped($thing_node));

    // Check determining if an entity type and bundle are mapped to Schema.org.
    $this->assertFalse($this->storage->isBundleMapped('node', 'page'));
    $this->assertTrue($this->storage->isBundleMapped('node', 'thing'));

    // Check getting the Schema.org type for an entity and bundle.
    $this->assertEquals('Thing', $this->storage->getSchemaType('node', 'thing'));

    // Check getting the Schema.org property name for an entity field mapping.
    $this->assertEquals('name', $this->storage->getSchemaPropertyName('node', 'thing', 'title'));
    $this->assertNull($this->storage->getSchemaPropertyName('node', 'thing', 'not_field'));
    $this->assertNull($this->storage->getSchemaPropertyName('node', 'not_thing', 'thing'));

    // Check getting a Schema.org property's range includes.
    $this->assertEquals(['Question' => 'Question'], $this->storage->getSchemaPropertyRangeIncludes('FAQPage', 'mainEntity'));

    // Check getting a Schema.org property's target bundles.
    $this->assertEquals(['image_object' => 'image_object'], $this->storage->getSchemaPropertyTargetBundles('node', 'Thing', 'image'));
    $this->assertEquals([], $this->storage->getSchemaPropertyTargetBundles('media', 'Thing', 'image'));

    // Check determining if Schema.org type is mapped to an entity.
    $this->assertTrue($this->storage->isSchemaTypeMapped('node', 'Thing'));
    $this->assertFalse($this->storage->isSchemaTypeMapped('node', 'NotThing'));
    $this->assertFalse($this->storage->isSchemaTypeMapped('not_node', 'Thing'));

    // Check loading by target entity id and Schema.org type.
    $this->assertEquals('node.thing', $this->storage->loadBySchemaType('node', 'Thing')->id());
    $this->assertNull($this->storage->loadBySchemaType('node', 'NotThing'));

    // Check loading by entity.
    $this->assertEquals('node.thing', $this->storage->loadByEntity($thing_node)->id());
    $this->assertNull($this->storage->loadByEntity($page_node));
  }

}
