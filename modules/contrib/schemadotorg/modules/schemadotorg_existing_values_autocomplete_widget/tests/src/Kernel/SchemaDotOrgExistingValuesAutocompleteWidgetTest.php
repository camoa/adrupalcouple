<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_existing_values_autocomplete_widget\Kernel;

use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Existing Values Autocomplete Widget.
 *
 * @covers _schemadotorg_existing_values_autocomplete_widget_enabled()
 * @covers schemadotorg_existing_values_autocomplete_widget_schemadotorg_property_field_alter()
 * @group schemadotorg
 */
class SchemaDotOrgExistingValuesAutocompleteWidgetTest extends SchemaDotOrgKernelEntityTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'existing_values_autocomplete_widget',
    'schemadotorg_existing_values_autocomplete_widget',
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

    $this->installConfig(['schemadotorg_existing_values_autocomplete_widget']);

    $this->appendSchemaTypeDefaultProperties('Person', 'alumniOf');

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org Existing Values Autocomplete Widget.
   */
  public function testExistingValuesAutocompleteWidget(): void {
    $this->createSchemaEntity('node', 'Person');

    /* ********************************************************************** */

    // Check that the alumniOf property/field use an Existing Values Autocomplete Widget.
    // @see schemadotorg_existing_values_autocomplete_widget_schemadotorg_property_field_alter()
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    $form_display = $entity_display_repository->getFormDisplay('node', 'person', 'default');
    $component = $form_display->getComponent('schema_alumni_of');
    $this->assertEquals('existing_autocomplete_field_widget', $component['type']);
  }

}
