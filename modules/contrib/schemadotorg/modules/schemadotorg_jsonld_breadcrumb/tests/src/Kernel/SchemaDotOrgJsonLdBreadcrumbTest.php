<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld_breadcrumb\Kernel;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD breadcrumb.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdBreadcrumbTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_jsonld',
    'schemadotorg_jsonld_breadcrumb',
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
    $this->installConfig(['schemadotorg_jsonld']);
    $this->manager = $this->container->get('schemadotorg_jsonld.manager');
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org JSON-LD breadcrumb.
   */
  public function testBreadcrumb(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    // Allow Schema.org Thing to have default properties.
    $this->config('schemadotorg.settings')
      ->set('schema_types.default_properties.Thing', ['name'])
      ->save();
    $this->createSchemaEntity('node', 'Thing');

    $node = Node::create([
      'type' => 'thing',
      'title' => 'Something',
    ]);
    $node->save();

    // Check building JSON-LD with breadcrumb for the entity's route.
    $expected_result = [
      [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' =>
          [
            [
              '@type' => 'ListItem',
              'position' => 1,
              'item' =>
                [
                  '@id' => Url::fromRoute('<front>')->setAbsolute()->toString(),
                  'name' => 'Home',
                ],
            ],
            [
              '@type' => 'ListItem',
              'position' => 2,
              'item' =>
                [
                  '@id' => $node->toUrl()->setAbsolute()->toString(),
                  'name' => 'Something',
                ],
            ],
          ],
      ],
      [
        '@context' => 'https://schema.org',
        '@type' => 'Thing',
        '@url' => $node->toUrl()->setAbsolute()->toString(),
        'name' => 'Something',
      ],
    ];
    $route_match = $this->manager->getEntityRouteMatch($node);
    $this->assertEquals($expected_result, $this->builder->build($route_match));
  }

}
