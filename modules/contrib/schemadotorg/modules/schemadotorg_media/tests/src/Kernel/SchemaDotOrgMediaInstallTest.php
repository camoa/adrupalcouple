<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_media\Kernel;

use Drupal\media\Entity\MediaType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org media installation.
 *
 * @covers \schemadotorg_media_install()
 * @group schemadotorg
 */
class SchemaDotOrgMediaInstallTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'media',
    'schemadotorg_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('media');
    $this->installConfig(['schemadotorg_media']);
  }

  /**
   * Test Schema.org media installation.
   */
  public function testInstall(): void {
    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
    ])->save();

    \Drupal::moduleHandler()->loadInclude('schemadotorg_media', 'install');
    schemadotorg_media_install(FALSE);

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = SchemaDotOrgMapping::load('media.image');

    // Confirm media.image mapping is created and mapped to ImageObject.
    $this->assertEquals('ImageObject', $mapping->getSchemaType());
  }

}
