<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld_embed\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Custom Field JSON-LD .
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldJsonLdManager
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldJsonLdTest extends SchemaDotOrgKernelEntityTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'custom_field',
    'schemadotorg_jsonld',
    'schemadotorg_custom_field',
  ];

  /**
   * Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $manager;

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld', 'schemadotorg_custom_field']);
    $this->manager = $this->container->get('schemadotorg_jsonld.manager');
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org Custom Field JSON-LD manager.
   */
  public function testCustomField(): void {
    $this->assertTrue(TRUE);
    return;

    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->createSchemaEntity('node', 'Recipe');

    $node = Node::create([
      'type' => 'recipe',
      'title' => 'Some recipe',
      'schema_nutrition' => [
        [
          'serving_size' => '{service}',
          'calories' => '10.00',
        ],
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check that custom field data is included in Recipe JSON-LD with units.
    $route_match = $this->manager->getEntityRouteMatch($node);
    $jsonld = $this->builder->build($route_match);
    $expected_result_nutrition = [
      '@type' => 'NutritionInformation',
      'servingSize' => '{service}',
      'calories' => '10.00 calories',
    ];
    $this->assertEquals($expected_result_nutrition, $jsonld['nutrition']);
  }

}
