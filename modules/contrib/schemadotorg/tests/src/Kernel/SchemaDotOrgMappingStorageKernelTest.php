<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgMappingStorage;

/**
 * Tests the Schema.org mapping storage.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingStorageKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * The Schema.org mapping storage.
   */
  protected SchemaDotOrgMappingStorage $mappingStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

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

    $image_node = Node::create(['type' => 'image_object', 'title' => 'Image']);
    $image_node->save();

    // Check determining if an entity is mapped to a Schema.org type.
    $this->assertFalse($this->mappingStorage->isEntityMapped($page_node));
    $this->assertTrue($this->mappingStorage->isEntityMapped($thing_node));

    // Check determining if an entity type and bundle are mapped to Schema.org.
    $this->assertFalse($this->mappingStorage->isBundleMapped('node', 'page'));
    $this->assertTrue($this->mappingStorage->isBundleMapped('node', 'thing'));

    // Check getting the Schema.org type for an entity and bundle.
    $this->assertEquals('Thing', $this->mappingStorage->getSchemaType('node', 'thing'));

    // Check getting the Schema.org property name for an entity field mapping.
    $this->assertEquals('name', $this->mappingStorage->getSchemaPropertyName('node', 'thing', 'title'));
    $this->assertNull($this->mappingStorage->getSchemaPropertyName('node', 'thing', 'not_field'));
    $this->assertNull($this->mappingStorage->getSchemaPropertyName('node', 'not_thing', 'thing'));

    // Check getting a Schema.org property's range includes.
    $this->assertEquals(['Question' => 'Question'], $this->mappingStorage->getSchemaPropertyRangeIncludes('FAQPage', 'mainEntity'));

    // Check getting a Schema.org property's target bundles.
    $this->assertEquals(['image_object' => 'image_object'], $this->mappingStorage->getSchemaPropertyTargetBundles('node', 'Thing', 'image'));
    $this->assertEquals([], $this->mappingStorage->getSchemaPropertyTargetBundles('media', 'Thing', 'image'));

    $this->assertEquals(['image_object' => 'image_object'], $this->mappingStorage->getSchemaPropertyTargetBundles('node', 'Thing', 'image'));
    $this->assertEquals([], $this->mappingStorage->getSchemaPropertyTargetBundles('media', 'Thing', 'image'));

    // Check getting Schema.org range includes target bundles.
    $this->assertEquals([], $this->mappingStorage->getRangeIncludesTargetBundles('node', ['Thing' => 'Thing']));
    $this->assertEquals(['image_object' => 'image_object'], $this->mappingStorage->getRangeIncludesTargetBundles('node', ['MediaObject' => 'MediaObject']));
    $this->assertEquals(['image_object' => 'image_object'], $this->mappingStorage->getRangeIncludesTargetBundles('node', ['ImageObject' => 'ImageObject']));

    // Check loading by target entity id and Schema.org type.
    $this->assertEquals('node.thing', $this->mappingStorage->loadBySchemaType('node', 'Thing')->id());
    $this->assertNull($this->mappingStorage->loadBySchemaType('node', 'NotThing'));

    // Check loading by entity.
    $this->assertEquals('node.thing', $this->mappingStorage->loadByEntity($thing_node)->id());
    $this->assertNull($this->mappingStorage->loadByEntity($page_node));
  }

}
