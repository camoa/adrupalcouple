<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonapi\Kernel;

use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Base test for the Schema.org JSON:API module.
 */
abstract class SchemaDotOrgJsonApiKernelTestBase extends SchemaDotOrgKernelTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'options',
    'file',
    'serialization',
    'jsonapi',
    'jsonapi_extras',
    'schemadotorg_jsonapi',
  ];

  /**
   * The JSON:API resource storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $resourceStorage;

  /**
   * The Schema.org mapping storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingStorage
   */
  protected $mappingStorage;

  /**
   * Schema.org JSON:API manager.
   *
   * @var \Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);
    $this->installConfig(['schemadotorg_jsonapi']);

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->installer->install();

    $this->mappingStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping');
    $this->resourceStorage = $this->container->get('entity_type.manager')->getStorage('jsonapi_resource_config');
    $this->manager = $this->container->get('schemadotorg_jsonapi.manager');

    // Set the Schema.org Blueprints JSON:API weight.
    // @see schemadotorg_jsonapi_install()
    module_set_weight('schemadotorg_jsonapi', 1);
  }

  /**
   * Load a JSON:API resource.
   *
   * @param string $id
   *   Resource ID.
   *
   * @return \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig
   *   A JSON:API resource.
   */
  protected function loadResource(string $id): JsonapiResourceConfig {
    $this->resourceStorage->resetCache([$id]);
    return $this->resourceStorage->load($id);
  }

}
