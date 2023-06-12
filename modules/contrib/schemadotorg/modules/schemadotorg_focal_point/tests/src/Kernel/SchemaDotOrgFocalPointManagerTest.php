<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_focal_point\Kernel;

use Drupal\image\Entity\ImageStyle;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org focal point manager.
 *
 * @group schemadotorg
 */
class SchemaDotOrgFocalPointManagerTest extends SchemaDotOrgKernelEntityTestBase {

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
   * The Schema.org focal point manager.
   *
   * @var \Drupal\schemadotorg_focal_point\SchemaDotOrgFocalPointManagerInterface
   */
  protected $focalPointManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('crop');
    $this->installEntitySchema('crop_type');
    $this->installConfig(['crop', 'focal_point', 'schemadotorg_focal_point']);

    $this->entityDisplayReository = $this->container->get('entity_display.repository');
    $this->focalPointManager = $this->container->get('schemadotorg_focal_point.manager');
  }

  /**
   * Test Schema.org focal point manager.
   */
  public function testFocalPointManager(): void {
    $image_styles = $this->config('schemadotorg_focal_point.settings')->get('image_styles');

    // Check resetting focal point image styles.
    $this->focalPointManager->resetImageStyles($image_styles);
    $this->assertNotNull(ImageStyle::load('4x3'));
    $this->assertNotNull(ImageStyle::load('3x4'));

    // Check deleting old focal point image styles.
    $image_styles = ['4x3' => $image_styles['4x3']];
    $this->focalPointManager->resetImageStyles($image_styles);
    $this->assertNotNull(ImageStyle::load('4x3'));
    $this->assertNull(ImageStyle::load('3x4'));
  }

}
