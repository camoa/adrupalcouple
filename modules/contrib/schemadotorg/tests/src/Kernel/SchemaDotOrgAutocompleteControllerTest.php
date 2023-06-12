<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Schema.org autocomplete controller.
 *
 * @coversClass \Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteControllerTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'field', 'text'];

  /**
   * The Schema.org autocomplete controller.
   *
   * @var \Drupal\schemadotorg\Controller\SchemaDotOrgAutocompleteController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->install();

    $this->controller = SchemaDotOrgAutocompleteController::create($this->container);
  }

  /**
   * Test the Schema.org autocomplete controller.
   */
  public function testAutocompleteController(): void {
    // Check searching for 'Thing' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'types');
    $this->assertEquals('[{"value":"ClothingStore","label":"ClothingStore"},{"value":"MensClothingStore","label":"MensClothingStore"},{"value":"Thing","label":"Thing"}]', $result->getContent());

    // Check searching for 'MensClothingStore' within Schema.org types returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'MensClothingStore']), 'types');
    $this->assertEquals('[{"value":"MensClothingStore","label":"MensClothingStore"}]', $result->getContent());

    // Check searching for 'Thing' within Schema.org properies returns 3 results.
    $result = $this->controller->autocomplete(new Request(['q' => 'Thing']), 'properties');
    $this->assertEquals('[]', $result->getContent());

    // Check searching for 'Male' within Schema.org types returns Gender
    // enumeration values.
    $result = $this->controller->autocomplete(new Request(['q' => 'Male']), 'types');
    $this->assertEquals('[{"value":"Female","label":"Female"},{"value":"Male","label":"Male"}]', $result->getContent());

    // Check searching for 'Male' within Schema.org Thing does NOT
    // return Gender enumeration values.
    $result = $this->controller->autocomplete(new Request(['q' => 'Male']), 'Thing');
    $this->assertEquals('[]', $result->getContent());
  }

}
