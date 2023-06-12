<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

/**
 * Tests the Schema.org installer service.
 *
 * @coversDefaultClass \Drupal\schemadotorg\SchemaDotOrgInstaller
 * @group schemadotorg
 */
class SchemaDotOrgInstallerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['media'];

  /**
   * The Schema.org installer service.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface
   */
  protected $installer;

  /**
   * The Schema.org mapping type storage.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface
   */
  protected $mappingTypeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installer = $this->container->get('schemadotorg.installer');
    $this->mappingTypeStorage = $this->container->get('entity_type.manager')->getStorage('schemadotorg_mapping_type');
  }

  /**
   * Tests SchemaDotOrgInstallerInterface::requirements().
   *
   * @covers ::requirements
   */
  public function testRequirements(): void {
    // Check installation recommended requirements.
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertEquals('Schema.org Blueprints: Recommended modules missing', $requirements['schemadotorg_recommended_modules']['title']);

    // Check installation recommended requirements exists.
    $requirements = $this->installer->requirements('runtime');
    $this->assertNotEmpty($requirements);
    $this->assertArrayHasKey('schemadotorg_integration_modules', $requirements);
    $this->assertEquals('Schema.org Blueprints: Integration modules missing', $requirements['schemadotorg_integration_modules']['title']);

    // Check installation recommended requirements does not exist.
    $this->uninstallModule('media');
    $requirements = $this->installer->requirements('runtime');
    $this->assertArrayNotHasKey('schemadotorg_integration_modules', $requirements);
  }

  /**
   * Tests SchemaDotOrgInstallerInterface::installModules().
   *
   * @covers ::installModules
   */
  public function testInstallModules(): void {
    // Check creating mapping types for modules that provide a content entities.
    $this->assertNull($this->mappingTypeStorage->load('storage'));
    $this->installer->installModules(['storage']);
    $this->assertNotNull($this->mappingTypeStorage->load('storage'));
  }

}
