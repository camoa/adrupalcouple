<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;

/**
 * Tests the Schema.org entity type builder service.
 *
 * @coversClass \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilder
 * @group schemadotorg
 */
class SchemaDotOrgEntityTypeBuilderTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface
   */
  protected $schemaEntityTypeBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
    $this->schemaEntityTypeBuilder = $this->container->get('schemadotorg.entity_type_builder');

    // Create teaser display mode.
    EntityViewMode::create([
      'id' => 'node.teaser',
      'label' => 'Teaser',
      'targetEntityType' => 'node',
    ])->save();

    // Create custom display mode.
    EntityViewMode::create([
      'id' => 'node.custom',
      'label' => 'Custom',
      'targetEntityType' => 'node',
    ])->save();

    // Create custom form mode.
    EntityFormMode::create([
      'id' => 'node.custom',
      'label' => 'Custom',
      'targetEntityType' => 'node',
    ])->save();
  }

  /**
   * Test Schema.org entity type builder.
   */
  public function testEntityTypeBuilder(): void {
    // Check adding an entity bundle.
    $values = [
      'entity' => [
        'label' => 'Thing',
        'id' => 'thing',
      ],
    ];
    $bundle_entity = $this->schemaEntityTypeBuilder->addEntityBundle('node_type', 'Thing', $values);
    $this->assertEquals('thing', $bundle_entity->id());
    $this->assertEquals('Thing', $bundle_entity->label());
    $this->assertEquals('Thing', $bundle_entity->schemaDotOrgType);
    $this->assertEquals([], $this->entityDisplayRepository->getFormModeOptionsByBundle('node', 'thing'));
    $this->assertEquals(['teaser' => 'Teaser'], $this->entityDisplayRepository->getViewModeOptionsByBundle('node', 'thing'));

    // Check that a 'teaser' view display is created for the Thing node type.
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'teaser');
    $this->assertFalse($view_display->isNew());

    // Check that a 'custom' view display is NOT created for the Thing node type.
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'custom');
    $this->assertTrue($view_display->isNew());

    // Enable custom form and view displays for Thing node type.
    $this->entityDisplayRepository->getFormDisplay('node', 'thing', 'custom')->save();
    $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'custom')->save();
    $this->assertEquals(['custom' => 'Custom'], $this->entityDisplayRepository->getFormModeOptionsByBundle('node', 'thing'));
    $this->assertEquals(['teaser' => 'Teaser', 'custom' => 'Custom'], $this->entityDisplayRepository->getViewModeOptionsByBundle('node', 'thing'));

    // Check adding an alternateName field to an entity.
    $field = [
      'name' => SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD,
      'type' => 'string',
      'label' => 'Alternate names',
      'machine_name' => 'schema_alternate_name',
      'description' => '',
      'unlimited' => '1',
      'required' => '1',
      'schema_type' => 'Thing',
      'schema_property' => 'alternateName',
    ];
    $this->schemaEntityTypeBuilder->addFieldToEntity('node', 'thing', $field);

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('node.thing.schema_alternate_name');
    $this->assertEquals('Alternate names', $field->label());
    $this->assertEquals('schema_alternate_name', $field->getName());

    // Check the Thing default form display mode.
    $form_components = $this->entityDisplayRepository->getFormDisplay('node', 'thing', 'default')->getComponents();
    $this->assertArrayHasKey('title', $form_components);
    $this->assertArrayHasKey('status', $form_components);
    $this->assertArrayHasKey('sticky', $form_components);
    $this->assertArrayHasKey('schema_alternate_name', $form_components);

    // Check the Thing custom form display mode.
    $form_components = $this->entityDisplayRepository->getFormDisplay('node', 'thing', 'custom')->getComponents();
    $this->assertArrayHasKey('schema_alternate_name', $form_components);

    // Check the Thing default view display mode.
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'default')->getComponents();
    $this->assertArrayHasKey('title', $view_components);
    $this->assertArrayHasKey('schema_alternate_name', $view_components);

    // Check the Thing custom view display mode.
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'custom')->getComponents();
    $this->assertArrayNotHasKey('schema_alternate_name', $view_components);

    // Check the Thing teaser view display mode.
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'teaser')->getComponents();
    $this->assertArrayNotHasKey('schema_alternate_name', $view_components);

    // Check adding a description (body) field to an entity.
    $field = [
      'name' => SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD,
      'type' => 'text_with_summary',
      'label' => 'Body',
      'machine_name' => 'body',
      'description' => '',
      'unlimited' => '0',
      'required' => '0',
      'schema_type' => 'Thing',
      'schema_property' => 'description',
    ];
    $this->schemaEntityTypeBuilder->addFieldToEntity('node', 'thing', $field);

    // Check that body is included in all form modes.
    $form_components = $this->entityDisplayRepository->getFormDisplay('node', 'thing', 'default')->getComponents();
    $this->assertArrayHasKey('body', $form_components);
    $form_components = $this->entityDisplayRepository->getFormDisplay('node', 'thing', 'custom')->getComponents();
    $this->assertArrayHasKey('body', $form_components);

    // Check that body is included in default and teaser display modes.
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'default')->getComponents();
    $this->assertArrayHasKey('body', $view_components);
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'teaser')->getComponents();
    $this->assertArrayHasKey('body', $view_components);
    $view_components = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'custom')->getComponents();
    $this->assertArrayNotHasKey('body', $view_components);

    // Check that the body displays the summary with the title hidden.
    $body_component = $this->entityDisplayRepository->getViewDisplay('node', 'thing', 'teaser')->getComponent('body');
    $this->assertEquals('text_summary_or_trimmed', $body_component['type']);
    $this->assertEquals('hidden', $body_component['label']);
  }

}
