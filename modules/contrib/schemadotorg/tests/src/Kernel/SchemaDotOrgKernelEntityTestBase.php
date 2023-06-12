<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\file\Entity\File;
use Drupal\media\MediaTypeInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Base class to testing entity type/bundle that are mapped to Schema.org types.
 *
 * @group schemadotorg
 */
abstract class SchemaDotOrgKernelEntityTestBase extends SchemaDotOrgKernelTestBase {
  use MediaTypeCreationTrait;
  use TestFileCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'media',
    'paragraphs',
    'field',
    'field_ui',
    'entity_reference_revisions',
    'file',
    'datetime',
    'image',
    'telephone',
    'text',
    'link',
    'options',
    'schemadotorg_media',
    'schemadotorg_paragraphs',
  ];

  /**
   * Tracks lazily installed entity schemas.
   *
   * @var array
   */
  protected $installedEntitySchemas = [
    'node' => 'node',
    'file' => 'file',
    'media' => 'media',
    'paragraph' => 'paragraph',
  ];

  /**
   * Tracks lazily installed entity config.
   *
   * @var array
   */
  protected $installedConfig = [
    'node' => ['node'],
    'media' => ['media', 'image', 'schemadotorg_media'],
    'paragraph' => ['paragraphs', 'schemadotorg_paragraphs'],
  ];

  /**
   * Tracks lazily installed entity schema.
   *
   * @var array
   */
  protected $installedSchemas = [
    'node' => [
      'module' => 'node',
      'schemas' => ['node_access'],
    ],
    'file' => [
      'module' => 'file',
      'schemas' => ['file_usage'],
    ],
  ];

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $mappingStorage;

  /**
   * The Schema.org mapping manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $mappingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');

    $this->installConfig(['schemadotorg']);
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);

    // Always install the user entity schema which is required by all entities.
    $this->installEntitySchema('user');
    $this->installEntitySchema('image_style');

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    $this->mappingStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
    $this->mappingManager = $this->container->get('schemadotorg.mapping_manager');
  }

  /**
   * Install entity dependencies.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function installEntityDependencies(string $entity_type_id): void {
    // Install the target entity type schema.
    if (isset($this->installedEntitySchemas[$entity_type_id])
      && $this->installedEntitySchemas[$entity_type_id] !== TRUE) {
      $this->installEntitySchema($entity_type_id);
      $this->installedEntitySchemas[$entity_type_id] = TRUE;
    }

    // Install the target entity type module config.
    if (isset($this->installedConfig[$entity_type_id])
      && $this->installedConfig[$entity_type_id] !== TRUE) {
      $modules = $this->installedConfig[$entity_type_id];
      $this->installConfig($modules);
      $this->installedConfig[$entity_type_id] = TRUE;
    }

    // Install the target entity type module schemas.
    if (isset($this->installedSchemas[$entity_type_id])
      && $this->installedSchemas[$entity_type_id] !== TRUE) {
      $schema = $this->installedSchemas[$entity_type_id];
      $this->installSchema($schema['module'], $schema['schemas']);
      $this->installedSchemas[$entity_type_id] = TRUE;
    }
  }

  /**
   * Create an entity type/bundle that is mapping to a Schema.org type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return \Drupal\schemadotorg\SchemaDotOrgMappingInterface|null
   *   The entity type/bundle's Schema.org mapping.
   */
  protected function createSchemaEntity(string $entity_type_id, string $schema_type): ?SchemaDotOrgMappingInterface {
    // Install the entity type dependencies.
    $this->installEntityDependencies($entity_type_id);

    // Create the entity type and mappings.
    $this->mappingManager->createType($entity_type_id, $schema_type);

    // Load the newly created Schema.org mapping.
    $mappings = $this->mappingStorage->loadByProperties([
      'target_entity_type_id' => $entity_type_id,
      'schema_type' => $schema_type,
    ]);
    return ($mappings) ? reset($mappings) : NULL;
  }

  /**
   * Create a test image file.
   *
   * @return \Drupal\file\Entity\File
   *   A test image file.
   */
  protected function createFileImage(): File {
    $this->installEntityDependencies('file');

    $file_uri = $this->getTestFiles('image')[0]->uri;
    $file_uri = str_replace('vfs://root', 'public://', $file_uri);
    $image = File::create([
      'uri' => $file_uri,
    ]);
    $image->setPermanent();
    $image->save();
    return $image;
  }

  /**
   * Create media image type.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   The image media type.
   */
  protected function createMediaImage(): MediaTypeInterface {
    return $this->createMediaType('image', ['id' => 'image', 'label' => 'Image']);
  }

}
