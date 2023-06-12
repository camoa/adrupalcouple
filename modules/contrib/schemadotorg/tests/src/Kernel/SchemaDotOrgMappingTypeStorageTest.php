<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Tests the Schema.org type manager service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
 * @group schemadotorg
 */
class SchemaDotOrgMappingTypeStorageTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'node',
    'user',
    'file',
    'schemadotorg_paragraphs',
  ];

  /**
   * The Schema.org mapping type storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg', 'schemadotorg_paragraphs']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Set Schema.org mapping storage.
    $this->storage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Test Schema.org mapping type storage.
   */
  public function testSchemaDotOrgMappingTypeStorage(): void {
    // Check getting entity types that implement Schema.org.
    $expected_entity_types = [
      'node' => 'node',
      'paragraph' => 'paragraph',
      'user' => 'user',
    ];
    $actual_entity_types = $this->storage->getEntityTypes();
    $this->assertEquals($expected_entity_types, $actual_entity_types);

    // Check getting entity types with bundles that implement Schema.org.
    $expected_bundle_entity_types = [
      'node' => 'node',
      'paragraph' => 'paragraph',
    ];
    $actual_bundle_entity_types = $this->storage->getEntityTypesWithBundles();
    $this->assertEquals($expected_bundle_entity_types, $actual_bundle_entity_types);

    // Check getting entity type bundles. (i.e node).
    $actual_entity_type_bundles = $this->storage->getEntityTypeBundles();
    $this->assertArrayHasKey('paragraph', $actual_entity_type_bundles);
    $this->assertInstanceOf(ContentEntityType::class, $actual_entity_type_bundles['paragraph']);

    // Check getting entity type bundle definitions. (i.e node_type).
    $actual_entity_type_bundle_definitions = $this->storage->getEntityTypeBundleDefinitions();
    $this->assertArrayHasKey('paragraph', $actual_entity_type_bundle_definitions);
    $this->assertInstanceOf(ConfigEntityType::class, $actual_entity_type_bundle_definitions['paragraph']);
  }

}
