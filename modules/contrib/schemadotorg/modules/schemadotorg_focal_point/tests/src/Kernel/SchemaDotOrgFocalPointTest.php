<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_focal_point\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org focal point.
 *
 * @covers schemadotorg_focal_point_schemadotorg_mapping_insert()
 * @covers schemadotorg_focal_point_schemadotorg_property_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgFocalPointTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'crop',
    'focal_point',
    'schemadotorg_focal_point',
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

    $this->installEntitySchema('crop');
    $this->installEntitySchema('crop_type');
    $this->installConfig(['crop', 'focal_point', 'schemadotorg_focal_point']);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org focal point.
   */
  public function testFocalPoint(): void {
    // Add primaryImageOfPage to WebPage.
    \Drupal::configFactory()->getEditable('schemadotorg.settings')
      ->set('schema_types.default_properties.WebPage', ['name', 'primaryImageOfPage', 'text'])
      ->save();

    // Check that existing image fields use focal point.
    // @see schemadotorg_focal_point_schemadotorg_mapping_insert()
    $this->createSchemaEntity('media', 'ImageObject');
    $form_display = $this->entityDisplayRepository->getFormDisplay('media', 'image');
    $component = $form_display->getComponent('field_media_image');
    $this->assertEquals('image_focal_point', $component['type']);
    $this->assertEquals([
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'medium',
      'preview_link' => TRUE,
      'offsets' => '50,50',
    ], $component['settings']);

    // Check that new image fields use focal point.
    // @see schemadotorg_focal_point_schemadotorg_property_field_alter()
    $this->config('schemadotorg.settings')
      ->set('schema_properties.default_fields.primaryImageOfPage', ['type' => 'image'])
      ->save();
    $this->createSchemaEntity('node', 'WebPage');
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'page');
    $component = $form_display->getComponent('schema_primary_image');
    $this->assertEquals('image_focal_point', $component['type']);
    $this->assertEquals([
      'progress_indicator' => 'throbber',
      'preview_image_style' => 'medium',
      'preview_link' => TRUE,
      'offsets' => '50,50',
    ], $component['settings']);
  }

}
