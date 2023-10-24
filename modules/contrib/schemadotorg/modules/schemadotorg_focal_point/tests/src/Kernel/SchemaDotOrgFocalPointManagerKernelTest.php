<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_focal_point\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\schemadotorg_focal_point\SchemaDotOrgFocalPointManagerInterface;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org focal point manager.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFocalPointManagerKernelTest extends SchemaDotOrgEntityKernelTestBase {

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
   */
  protected EntityDisplayRepositoryInterface $entityDisplayRepository;

  /**
   * The Schema.org focal point manager.
   */
  protected SchemaDotOrgFocalPointManagerInterface $focalPointManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('crop');
    $this->installEntitySchema('crop_type');
    $this->installConfig(['crop', 'focal_point', 'schemadotorg_focal_point']);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
    $this->focalPointManager = $this->container->get('schemadotorg_focal_point.manager');
  }

  /**
   * Test Schema.org focal point manager.
   */
  public function testFocalPointManager(): void {
    $image_styles = $this->config('schemadotorg_focal_point.settings')->get('image_styles');

    // Check resetting focal point image styles.
    $this->focalPointManager->resetImageStyles($image_styles);
    $this->assertNotNull(ImageStyle::load('4x3w1200'));
    $this->assertNotNull(ImageStyle::load('4x3w300'));
    $this->assertNotNull(ImageStyle::load('3x4w900'));
    $this->assertNotNull(ImageStyle::load('3x4w300'));

    // Check the '4:3 (1200×900)' image style.
    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = ImageStyle::load('4x3w1200');
    $this->assertEquals('4:3 (1200×900)', $image_style->label());
    $effects = $image_style->get('effects');
    $effect = reset($effects);
    unset($effect['uuid']);
    $expected_effect = [
      'id' => 'focal_point_scale_and_crop',
      'weight' => NULL,
      'data' => [
        'width' => 1200,
        'height' => 900,
        'crop_type' => 'focal_point',
      ],
    ];
    $this->assertEquals($expected_effect, $effect);

    // Check the '4:3 (300)' image style.
    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = ImageStyle::load('4x3w300');
    $this->assertEquals('4:3 (300×225)', $image_style->label());
    $effects = $image_style->get('effects');
    $effect = reset($effects);
    unset($effect['uuid']);
    $expected_effect = [
      'id' => 'focal_point_scale_and_crop',
      'weight' => NULL,
      'data' => [
        'width' => 300,
        'height' => 225,
        'crop_type' => 'focal_point',
      ],
    ];
    $this->assertEquals($expected_effect, $effect);

    // Check deleting old focal point image styles.
    $image_styles = ['4x3' => $image_styles['4x3']];
    $this->focalPointManager->resetImageStyles($image_styles);
    $this->assertNotNull(ImageStyle::load('4x3w1200'));
    $this->assertNotNull(ImageStyle::load('4x3w300'));
    $this->assertNull(ImageStyle::load('3x4w900'));
    $this->assertNull(ImageStyle::load('3x4w300'));
  }

}
