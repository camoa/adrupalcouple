<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_descriptions\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgEntityKernelTestBase;

/**
 * Tests the functionality of the Schema.org descriptions.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDescriptionsKernelTest extends SchemaDotOrgEntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'schemadotorg_descriptions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(self::$modules);
  }

  /**
   * Test Schema.org descriptions.
   */
  public function testDescriptions(): void {
    $this->createSchemaEntity('node', 'Event');

    // Check the node type description is empty when stored via configuration.
    $this->assertEmpty(\Drupal::configFactory()->getEditable('node.type.event')->get('description'));

    // Check the node type description is populate with the Schema.org comment.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load('event');
    $this->assertEquals('An event happening at a certain time and location, such as a concert, lecture, or festival.', $node_type->getDescription());
  }

}
