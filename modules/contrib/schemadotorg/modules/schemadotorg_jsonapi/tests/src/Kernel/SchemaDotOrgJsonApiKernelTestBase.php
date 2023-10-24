<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonapi\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\jsonapi_extras\Entity\JsonapiResourceConfig;
use Drupal\schemadotorg\SchemaDotOrgInstallerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingStorage;
use Drupal\schemadotorg_jsonapi\SchemaDotOrgJsonApiManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Base test for the Schema.org JSON:API module.
 */
abstract class SchemaDotOrgJsonApiKernelTestBase extends SchemaDotOrgEntityKernelTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'serialization',
    'jsonapi',
    'jsonapi_extras',
    'schemadotorg_jsonapi',
  ];

  /**
   * The Schema.org installer.
   */
  protected SchemaDotOrgInstallerInterface $installer;

  /**
   * The JSON:API resource storage.
   */
  protected ConfigEntityStorageInterface $resourceStorage;

  /**
   * The Schema.org mapping storage.
   */
  protected SchemaDotOrgMappingStorage $mappingStorage;

  /**
   * Schema.org JSON:API manager.
   */
  protected SchemaDotOrgJsonApiManagerInterface $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_jsonapi']);

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
