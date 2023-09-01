<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;

/**
 * Tests the Schema.org mapping manager service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgApi
 * @group schemadotorg
 */
class SchemaDotOrgMappingManagerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'field_ui',
    'file',
    'datetime',
    'image',
    'paragraphs',
    'system',
    'telephone',
    'text',
    'link',
    'options',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
    $this->installEntitySchema('user');

    $this->installConfig(['schemadotorg']);
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');

    // Get the Schema.org mapping manager.
    $this->mappingManager = $this->container->get('schemadotorg.mapping_manager');
  }

  /**
   * Tests SchemaDotOrgMappingManager.
   */
  public function testMappingManager(): void {
    // Checking getting ignored Schema.org properties.
    $this->assertArrayHasKey('accessMode', $this->mappingManager->getIgnoredProperties());

    // Check getting Schema.org mapping default values.
    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Event');
    $this->assertEquals('Event', $mapping_defaults['entity']['label']);
    $this->assertEquals('event', $mapping_defaults['entity']['id']);
    $this->assertStringStartsWith('An event', $mapping_defaults['entity']['description']);
    $expected = [
      'name' => 'title',
      'type' => 'string',
      'label' => 'Name',
      'machine_name' => 'name',
      'unlimited' => FALSE,
      'required' => FALSE,
      'description' => 'The name of the item.',
    ];
    $this->assertEquals($expected, $mapping_defaults['properties']['name']);
    $this->assertEquals(SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD, $mapping_defaults['properties']['description']['name']);
    $this->assertEquals('', $mapping_defaults['properties']['alternateName']['name']);

    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Person');
    $expected = [
      'name' => SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD,
      'type' => 'string',
      'label' => 'First name',
      'machine_name' => 'given_name',
      'unlimited' => FALSE,
      'required' => TRUE,
      'description' => 'Given name. In the U.S., the first name of a Person.',
    ];
    $this->assertEquals($expected, $mapping_defaults['properties']['givenName']);

    // Check getting Schema.org mapping default values for entity w/o bundles.
    $mapping_defaults = $this->mappingManager->getMappingDefaults('user', NULL, 'Person');
    $this->assertEquals('User', $mapping_defaults['entity']['label']);
    $this->assertEquals('user', $mapping_defaults['entity']['id']);

    // Check getting Schema.org mapping with a customized default type.
    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'FAQPage');
    $this->assertNotEquals('faq_page', $mapping_defaults['entity']['id']);
    $this->assertNotEquals('FAQ Page', $mapping_defaults['entity']['label']);
    $this->assertEquals('faq', $mapping_defaults['entity']['id']);
    $this->assertEquals('FAQ', $mapping_defaults['entity']['label']);

    // Check getting Schema.org mapping default values with custom defaults.
    $mapping_defaults = $this->mappingManager->getMappingDefaults(
      'node',
      NULL,
      'Event',
      [
        'entity' => ['label' => 'Custom event label'],
        'properties' => [
          'name' => ['label' => 'Custom name label'],
          'description' => FALSE,
          'alternateName' => TRUE,
        ],
      ]
    );
    $this->assertEquals('Custom event label', $mapping_defaults['entity']['label']);
    $this->assertEquals('title', $mapping_defaults['properties']['name']['name']);
    $this->assertEquals('Custom name label', $mapping_defaults['properties']['name']['label']);
    $this->assertEquals('', $mapping_defaults['properties']['description']['name']);
    $this->assertEquals(SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD, $mapping_defaults['properties']['alternateName']['name']);

    // Check getting Schema.org mapping default values with custom bundle.
    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', 'custom', 'Event');
    $this->assertEquals('custom', $mapping_defaults['entity']['id']);

    // Check getting Schema.org mapping default entity values
    // with label and id prefixes.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type */
    $mapping_type = $this->entityTypeManager->getStorage('schemadotorg_mapping_type')->load('node');
    $mapping_type
      ->set('label_prefix', 'Schema.org: ')
      ->set('id_prefix', 'schema_')
      ->save();
    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Event');
    $this->assertEquals('Schema.org: Event', $mapping_defaults['entity']['label']);
    $this->assertEquals('schema_event', $mapping_defaults['entity']['id']);
    $mapping_type
      ->set('label_prefix', '')
      ->set('id_prefix', '')
      ->save();

    // Check saving a Schema.org mapping.
    $mapping_defaults = $this->mappingManager->getMappingDefaults('node', NULL, 'Event');
    $mapping = $this->mappingManager->saveMapping('node', 'Event', $mapping_defaults);
    $this->assertEquals('node', $mapping->getTargetEntityTypeId());
    $this->assertEquals('event', $mapping->getTargetBundle());
    $this->assertEquals('Event', $mapping->getSchemaType());

    // Check create entity type validation.
    try {
      $this->mappingManager->createTypeValidate('not_entity', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The entity type 'not_entity' is not valid. Please select a entity type (node, user).");
    }

    // Check create schema type validation.
    try {
      $this->mappingManager->createTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "The Schema.org type 'not_schema' is not valid.");
    }

    // Check create schema mapping validation.
    try {
      $this->mappingManager->createType('paragraph', 'Thing');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "Mapping type 'paragraph' does not exist and is required to create a Schema.org 'Thing'.");
    }

    // Check creating user:Person type.
    $this->mappingManager->createType('user', 'Person');
    $mapping = SchemaDotOrgMapping::load('user.user');
    $this->assertEquals('user', $mapping->getTargetEntityTypeId());
    $this->assertEquals('user', $mapping->getTargetBundle());

    // Check create node:Article and check that the title label is set to 'Headline'.
    $this->mappingManager->createType('node', 'Article');
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', 'article');
    /** @var \Drupal\Core\Field\BaseFieldDefinition $title_field */
    $title_field = $field_definitions['title'];
    $this->assertEquals('Headline', $title_field->getConfig('article')->getLabel());

    /* ********************************************************************** */
    // Delete.
    /* ********************************************************************** */

    // Check delete schema mapping validation.
    try {
      $this->mappingManager->deleteTypeValidate('node', 'not_schema');
    }
    catch (\Exception $exception) {
      $this->assertEquals($exception->getMessage(), "No Schema.org mapping exists for not_schema (node).");
    }

    // Check deleting user:Person type.
    $this->mappingManager->deleteType('user', 'Person');
    \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping')->resetCache();
    $this->assertNull(SchemaDotOrgMapping::load('user.user'));
  }

}
