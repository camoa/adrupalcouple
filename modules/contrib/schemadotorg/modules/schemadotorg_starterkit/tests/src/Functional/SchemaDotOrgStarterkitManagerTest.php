<?php

declare(strict_types = 1);

namespace Drupal\Tests\starterkit\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org starterkit manager.
 *
 * @covers \Drupal\starterkit\SchemaDotOrgTaxonomyPropertyVocabularyManagerTest;
 * @group schemadotorg
 */
class SchemaDotOrgStarterkitManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'user',
    'node',
    'schemadotorg_starterkit',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org starterkit manager service.
   *
   * @var \Drupal\schemadotorg_starterkit\SchemaDotOrgStarterkitManagerInterface
   */
  protected $schemaStarterkitManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_starterkit']);
    $this->installEntityDependencies('media');
    $this->installEntityDependencies('node');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->schemaStarterkitManager = $this->container->get('schemadotorg_starterkit.manager');
  }

  /**
   * Test Schema.org starterkit manager.
   */
  public function testManager(): void {
    // Check determining if a module is Schema.org Blueprints Starter Kit.
    $this->assertFalse($this->schemaStarterkitManager->isStarterkit('schemadotorg'));
    $this->assertFalse($this->schemaStarterkitManager->isStarterkit('missing_module'));
    $this->assertTrue($this->schemaStarterkitManager->isStarterkit('schemadotorg_starterkit_test'));

    // Check getting a list of Schema.org starter kits.
    $starterkits = $this->schemaStarterkitManager->getStarterkits();
    $this->assertArrayHasKey('schemadotorg_starterkit_test', $starterkits);

    // Check getting a Schema.org starterkit's module info.
    $this->assertIsArray($this->schemaStarterkitManager->getStarterkit('schemadotorg_starterkit_test'));

    // Check getting a module's Schema.org Blueprints starterkit settings.
    $settings = $this->schemaStarterkitManager->getStarterkitSettings('schemadotorg_starterkit_test');
    $this->assertEquals('Something', $settings['types']['node:Thing']['entity']['label']);
    $this->assertEquals('_add_', $settings['types']['node:Thing']['properties']['name']['name']);
    $this->assertEquals('_add_', $settings['types']['node:Thing']['properties']['description']['name']);
    $this->assertEquals('_add_', $settings['types']['node:Thing']['properties']['image']['name']);
  }

}
