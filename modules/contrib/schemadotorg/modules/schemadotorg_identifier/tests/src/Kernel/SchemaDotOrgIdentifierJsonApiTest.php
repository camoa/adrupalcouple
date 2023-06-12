<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_identifier\Kernel;

use Drupal\Tests\schemadotorg_jsonapi\Kernel\SchemaDotOrgJsonApiKernelTestBase;

/**
 * Tests the functionality of the Schema.org identifier JSON:API support.
 *
 * @covers schemadotorg_identifier_jsonapi_resource_config_presave()
 * @group schemadotorg
 */
class SchemaDotOrgIdentifierJsonApiTest extends SchemaDotOrgJsonApiKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_identifier',
  ];

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

    $this->installEntitySchema('node');
    $this->installConfig(['schemadotorg_identifier']);

    $this->mappingManager = $this->container->get('schemadotorg.mapping_manager');
  }

  /**
   * Test Schema.org identifier JSON:API support.
   */
  public function testIdentifierJsonApi(): void {
    $this->mappingManager->createType('node', 'MedicalBusiness');

    // Check that JSON:API resource was created for Thing.
    /** @var \Drupal\jsonapi_extras\Entity\JsonapiResourceConfig $resource */
    $resource = $this->resourceStorage->load('node--medical_business');
    $resource_fields = $resource->get('resourceFields');
    $expected_result = [
      'disabled' => FALSE,
      'fieldName' => 'schema_identifier_npi',
      'publicName' => 'npi',
      'enhancer' => ['id' => ''],
    ];
    $this->assertEquals($expected_result, $resource_fields['schema_identifier_npi']);
  }

}
