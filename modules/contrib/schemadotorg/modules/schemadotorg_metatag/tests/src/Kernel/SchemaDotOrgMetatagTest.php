<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_metatag\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org metatag.
 *
 * @covers schemadotorg_metatag_schemadotorg_mapping_insert()
 * @group schemadotorg
 */
class SchemaDotOrgMetatagTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'token',
    'metatag',
    'schemadotorg_metatag',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['metatag']);
    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org metatag.
   */
  public function testMetatag(): void {
    // Create node:place.
    $this->createSchemaEntity('node', 'Place');

    // Check creating meta tag field storage.
    $this->assertNotNull(FieldStorageConfig::loadByName('node', 'field_metatag'));

    // Check creating meta tag field instance.
    $this->assertNotNull(FieldConfig::loadByName('node', 'place', 'field_metatag'));

    // Check setting meta tag component in the default form display.
    $expected_component = [
      'type' => 'metatag_firehose',
      'weight' => 99,
      'region' => 'content',
      'settings' => [
        'sidebar' => TRUE,
        'use_details' => TRUE,
      ],
      'third_party_settings' => [],
    ];
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'place', 'default');
    $this->assertEquals($expected_component, $form_display->getComponent('field_metatag'));
  }

}
