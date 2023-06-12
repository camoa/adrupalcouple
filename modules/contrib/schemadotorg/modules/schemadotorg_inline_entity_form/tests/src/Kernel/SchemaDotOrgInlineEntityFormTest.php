<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_inline_entity_form\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org inline entity form.
 *
 * @covers _schemadotorg_inline_entity_form_enabled()
 * @covers schemadotorg_inline_entity_form_schemadotorg_property_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgInlineEntityFormTest extends SchemaDotOrgKernelEntityTestBase {

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  /**
   * Disabled config schema checking temporarily until smart date fixes missing schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'inline_entity_form',
    'schemadotorg_inline_entity_form',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_inline_entity_form']);

    $this->appendSchemaTypeDefaultProperties('Person', 'alumniOf');

    // Clear the default type for alumniOf.
    $this->config('schemadotorg.settings')
      ->clear('schema_properties.default_fields.alumniOf.type')
      ->save();

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org inline entity form.
   */
  public function testInlineEntityForm(): void {
    // Use an inline entity form for Person:alumniOf.
    $this->config('schemadotorg_inline_entity_form.settings')
      ->set('default_properties', ['Person--alumniOf'])
      ->save();

    // Create organization to be used as the entity reference target for
    // Patient:alumniOf.
    $this->createSchemaEntity('node', 'Organization');

    // Create a patient instead of a person to test inheritance.
    // @see _schemadotorg_inline_entity_form_enabled()
    $this->createSchemaEntity('node', 'Patient');

    /* ********************************************************************** */

    // Check that the alumniOf property/field use an inline entity form.
    // @see schemadotorg_inline_entity_form_schemadotorg_property_field_alter()
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $form_display = $entity_display_repository->getFormDisplay('node', 'patient', 'default');
    $component = $form_display->getComponent('schema_alumni_of');
    $this->assertEquals('inline_entity_form_complex', $component['type']);
    $this->assertTrue($component['settings']['allow_existing']);
    $this->assertTrue($component['settings']['allow_duplicate']);
    $this->assertTrue($component['settings']['collapsible']);
    $this->assertTrue($component['settings']['revision']);

    // Check that Organization does not have an inline entity form display.
    // @see schemadotorg_inline_entity_form_node_type_insert()
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'organization', 'inline_entity_form');
    $this->assertTrue($form_display->isNew());

    // Check that Patient has an inline entity form display.
    // @see schemadotorg_inline_entity_form_node_type_insert()
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'patient', 'inline_entity_form');
    $this->assertFalse($form_display->isNew());

    // Check that Patient only has 'status' base field.
    // @see schemadotorg_inline_entity_form_node_type_insert()
    // @see \Drupal\node\Entity\Node::baseFieldDefinitions
    $this->assertNotNull($form_display->getComponent('title'));
    $this->assertNotNull($form_display->getComponent('status'));
    $this->assertNull($form_display->getComponent('uid'));
    $this->assertNull($form_display->getComponent('created'));
    $this->assertNull($form_display->getComponent('promote'));
    $this->assertNull($form_display->getComponent('sticky'));
  }

}
