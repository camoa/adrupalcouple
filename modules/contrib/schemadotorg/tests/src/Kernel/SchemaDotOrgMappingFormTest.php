<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\node\Entity\NodeType;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\schemadotorg\Form\SchemaDotOrgMappingForm;

/**
 * Tests the Schema.org mapping form.
 *
 * @coversClass \Drupal\schemadotorg\Form\SchemaDotOrgMappingForm
 * @group schemadotorg
 */
class SchemaDotOrgMappingFormTest extends SchemaDotOrgKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'node', 'field'];

  /**
   * A node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * A Schema.org mapping entity for a node.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $nodeMapping;

  /**
   * A Schema.org mapping entity for a user.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface
   */
  protected $userMapping;

  /**
   * The Schema.org mapping form.
   *
   * @var \Drupal\schemadotorg\Form\SchemaDotOrgMappingForm
   */
  protected $entityForm;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('schemadotorg_mapping');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->installSchema('schemadotorg', ['schemadotorg_types', 'schemadotorg_properties']);
    $this->installConfig(['schemadotorg']);

    // Import CSV data into the Schema.org type and properties tables.
    /** @var \Drupal\schemadotorg\SchemaDotOrgInstallerInterface $installer */
    $installer = $this->container->get('schemadotorg.installer');
    $installer->importTables();

    // Create Thing node with field.
    $node_type = NodeType::create([
      'type' => 'thing',
      'name' => 'Thing',
    ]);
    $node_type->save();
    $this->nodeType = $node_type;

    // Create Thing with mapping.
    $node_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
      'schema_properties' => [
        'title' => 'name',
      ],
    ]);
    $node_mapping->save();
    $this->nodeMapping = $node_mapping;

    // Create user with Person mapping.
    $user_mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'user',
      'target_bundle' => 'user',
      'schema_type' => 'Person',
      'schema_properties' => [],
    ]);
    $user_mapping->save();
    $this->userMapping = $user_mapping;

    // Set Schema.org mapping form.
    $this->entityForm = SchemaDotOrgMappingForm::create($this->container)
      ->setModuleHandler($this->container->get('module_handler'));
  }

  /**
   * Test Schema.org mapping form.
   */
  public function testSchemaDotOrgMappingForm(): void {
    // Display node (with bundle) mapping form.
    $this->entityForm->setEntity($this->nodeMapping);
    $form = $this->entityForm->buildForm([], new FormState());
    $this->assertEquals('Content type', $form['entity_type']['#title']);
    $this->assertEquals('Thing', $form['entity_type']['link']['#title']);
    $this->assertEquals(' (thing)', $form['entity_type']['link']['#suffix']);
    $this->assertEquals('Thing', $form['schema_type']['label']['#title']);
    $this->assertArrayHasKey('schema_properties', $form);
    $this->assertArrayNotHasKey('actions', $form);

    // Display user (without bundle or properties) mapping form.
    $this->entityForm->setEntity($this->userMapping);
    $form = $this->entityForm->buildForm([], new FormState());
    $this->assertEquals('Entity type', $form['entity_type']['#title']);
    $this->assertEquals('User', $form['entity_type']['#markup']);
    $this->assertEquals('Person', $form['schema_type']['label']['#title']);
    $this->assertArrayNotHasKey('schema_properties', $form);
    $this->assertArrayNotHasKey('actions', $form);
  }

}
