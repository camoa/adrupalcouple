<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_role\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org role JSON-LD reference.
 *
 * @covers schemadotorg_role_schemadotorg_property_field_alter()
 * @covers schemadotorg_role_schemadotorg_jsonld_schema_property_alter()
 * @group schemadotorg
 */
class SchemaDotOrgRoleReferenceJsonLdTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_jsonld',
    'schemadotorg_role',
    'entity_reference_override',
  ];

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
    $this->installConfig(['schemadotorg_role', 'schemadotorg_jsonld']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org role.
   */
  public function testRole(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    $this->appendSchemaTypeDefaultProperties('Organization', 'member');
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');

    $person_node = Node::create([
      'type' => 'person',
      'title' => 'John Smith',
    ]);
    $person_node->save();

    $organization_node = Node::create([
      'type' => 'organization',
      'title' => 'Organization',
      'schema_member' => [
        [
          'target_id' => $person_node->id(),
          'override' => 'President',
        ],
      ],
    ]);
    $organization_node->save();

    /* ********************************************************************** */

    // Check that the JSON-LD member property is using roles.
    $jsonld = $this->builder->buildEntity($organization_node);
    $expected_member = [
      [
        '@type' => 'Role',
        'roleName' => 'President',
        'member' =>
          [
            '@type' => 'Person',
            '@url' => $person_node->toUrl()->setAbsolute()->toString(),
            'name' => 'John Smith',
          ],
      ],
    ];
    $this->assertEquals($expected_member, $jsonld['member']);
  }

}
