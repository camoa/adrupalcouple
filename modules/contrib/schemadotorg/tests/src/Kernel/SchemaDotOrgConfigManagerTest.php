<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org config manager service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgConfigManager
 * @group schemadotorg
 */
class SchemaDotOrgConfigManagerTest extends SchemaDotOrgKernelTestBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org config manager service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgConfigManagerInterface
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installConfig(['schemadotorg']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->configManager = $this->container->get('schemadotorg.config_manager');
  }

  /**
   * Tests SchemaDotOrgConfigManager.
   */
  public function testConfigManager(): void {
    $config = $this->config('schemadotorg.settings');

    // Check setting Schema.org type's default properties.
    $this->assertEquals(['inLanguage'], $config->get('schema_types.default_properties.CreativeWork'));
    $this->configManager->setSchemaTypeDefaultProperties('CreativeWork', ['about']);
    $this->assertEquals(['about', 'inLanguage'], $config->get('schema_types.default_properties.CreativeWork'));

    // Check unsetting Schema.org type's default properties.
    $this->configManager->unsetSchemaTypeDefaultProperties('CreativeWork', ['about']);
    $this->assertEquals(['inLanguage'], $config->get('schema_types.default_properties.CreativeWork'));

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager
      ->getStorage('schemadotorg_mapping_type');

    // Check updating a Schema.org mapping type's default properties.
    $mapping_type = $mapping_type_storage->load('node');
    $mapping_type->set('default_schema_type_properties', ['Thing' => []]);
    $mapping_type->save();

    // Check setting Schema.org mapping type's default properties.
    $this->configManager->setMappingTypeSchemaTypeDefaultProperties('node', 'Thing', ['subjectOf']);
    $mapping_type_storage->resetCache();
    $mapping_type = $mapping_type_storage->load('node');
    $this->assertEquals(['Thing' => ['subjectOf']], $mapping_type->get('default_schema_type_properties'));

    // Check unsetting Schema.org mapping type's default properties.
    $this->configManager->unsetMappingTypeSchemaTypeDefaultProperties('node', 'Thing', ['subjectOf']);
    $mapping_type_storage->resetCache();
    $mapping_type = $mapping_type_storage->load('node');
    $this->assertEquals(['Thing' => []], $mapping_type->get('default_schema_type_properties'));
  }

}
