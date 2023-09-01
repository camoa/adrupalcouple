<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org subtype module.
 *
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_ui',
    'schemadotorg_subtype',
  ];

  /**
   * Test Schema.org subtype UI.
   */
  public function testSubtype(): void {
    $assert_session = $this->assertSession();

    $config = $this->config('schemadotorg_subtype.settings');

    /* ********************************************************************** */
    // Mapping defaults.
    // @see schemadotorg_subtype_schemadotorg_mapping_defaults_alter()
    /* ********************************************************************** */

    // Check mapping defaults for Schema.org type that supports subtyping.
    $defaults = $this->getMappingDefaults('node', NULL, 'Person');
    $this->assertArrayHasKey('subtype', $defaults['properties']);
    $this->assertEquals('', $defaults['properties']['subtype']['name']);
    $this->assertEquals('list_string', $defaults['properties']['subtype']['type']);
    $this->assertEquals('Subtype', $defaults['properties']['subtype']['label']);
    $this->assertEquals('person_subtype', $defaults['properties']['subtype']['machine_name']);
    $this->assertEquals($config->get('default_field_description'), $defaults['properties']['subtype']['description']);
    $this->assertEquals(['Patient' => 'Patient'], $defaults['properties']['subtype']['allowed_values']);

    // Check mapping default sfor Schema.org type that does not support subtyping.
    $defaults = $this->getMappingDefaults('node', NULL, 'Patient');
    $this->assertArrayNotHasKey('properties', $defaults);

    // Check mapping default for Schema.org type that has subtype enabled.
    $defaults = $this->getMappingDefaults('node', NULL, 'Event');
    $this->assertEquals('_add_', $defaults['properties']['subtype']['name']);

    // Check mapping defaults for Schema.org type that has customized allowed_values.
    $defaults = $this->getMappingDefaults('node', NULL, 'WebPage');
    $expected_allowed_values = [
      'AboutPage' => 'About Page',
      'ContactPage' => 'Contact Page',
      'MedicalWebPage' => 'Medical Web Page',
    ];
    $this->assertEquals($expected_allowed_values, $defaults['properties']['subtype']['allowed_values']);

    /* ********************************************************************** */
    // Schema.org mapping UI form alter.
    // @see schemadotorg_subtype_form_schemadotorg_mapping_form_alter()
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Check no subtype field on Schema.org type select form.
    $this->drupalGet('/admin/structure/types/schemadotorg');
    $assert_session->responseNotContains('Enable Schema.org subtyping');

    // Check that subtype field appears but is not checked by default.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Person']]);
    $assert_session->responseContains('Enable Schema.org subtyping');
    $assert_session->checkboxNotChecked('mapping[properties][subtype][field][name]');

    // Check that subtype field does appear when not supported.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Patient']]);
    $assert_session->responseNotContains('Enable Schema.org subtyping');

    // Check that subtype field is checked by default.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Event']]);
    $assert_session->responseContains('Enable Schema.org subtyping');
    $assert_session->checkboxChecked('mapping[properties][subtype][field][name]');

    // Create the Event Schema.org type mapping.
    $this->submitForm([], 'Save');
    $assert_session->responseContains('The content type <em class="placeholder">Event</em> has been added.');

    // Check mapping defaults for existing Schema.org type just return the field name.
    $defaults = $this->getMappingDefaults('node', 'event', 'Event');
    $expected_subtype_properties = [
      'name' => 'schema_event_subtype',
      'type' => 'list_string',
      'label' => 'Subtype',
      'description' => 'A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.',
      'machine_name' => 'event_subtype',
      'allowed_values' => [
        'BusinessEvent' => 'Business Event',
        'ChildrensEvent' => 'Childrens Event',
        'ComedyEvent' => 'Comedy Event',
        'CourseInstance' => 'Course Instance',
        'DanceEvent' => 'Dance Event',
        'DeliveryEvent' => 'Delivery Event',
        'EducationEvent' => 'Education Event',
        'EventSeries' => 'Event Series',
        'ExhibitionEvent' => 'Exhibition Event',
        'Festival' => 'Festival',
        'FoodEvent' => 'Food Event',
        'Hackathon' => 'Hackathon',
        'LiteraryEvent' => 'Literary Event',
        'MusicEvent' => 'Music Event',
        'PublicationEvent' => 'Publication Event',
        'BroadcastEvent' => '- Broadcast Event',
        'OnDemandEvent' => '- On Demand Event',
        'SaleEvent' => 'Sale Event',
        'ScreeningEvent' => 'Screening Event',
        'SocialEvent' => 'Social Event',
        'SportsEvent' => 'Sports Event',
        'TheaterEvent' => 'Theater Event',
        'VisualArtsEvent' => 'Visual Arts Event',
      ],
    ];
    $this->assertEquals($expected_subtype_properties, $defaults['properties']['subtype']);

    /* ********************************************************************** */
    // Mapping defaults configuration.
    // @see schemadotorg_subtype_form_schemadotorg_types_settings_form_alter()
    /* ********************************************************************** */

    // Update subtype configuration settings.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/types');
    $edit = [
      'schemadotorg_subtype[default_field_label]' => 'Type',
      'schemadotorg_subtype[default_field_suffix]' => '_type',
      'schemadotorg_subtype[default_field_description]' => 'Custom subtype description',
      'schemadotorg_subtype[default_subtypes]' => 'Person',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check mapping defaults with new subtype configuration settings.
    $defaults = $this->getMappingDefaults('node', NULL, 'Person');
    $this->assertEquals('_add_', $defaults['properties']['subtype']['name']);
    $this->assertEquals('Type', $defaults['properties']['subtype']['label']);
    $this->assertEquals('person_type', $defaults['properties']['subtype']['machine_name']);
    $this->assertEquals('Custom subtype description', $defaults['properties']['subtype']['description']);
  }

  /**
   * Get the mapping defaults for a Schema.org mapping.
   *
   * @param string $entity_type_id
   *   THe entity type.
   * @param string|null $bundle
   *   The bundle.
   * @param string $schema_type
   *   The Schema.org type.
   *
   * @return array
   *   The mapping defaults.
   */
  protected function getMappingDefaults(string $entity_type_id, ?string $bundle, string $schema_type): array {
    $defaults = [];
    schemadotorg_subtype_schemadotorg_mapping_defaults_alter($defaults, $entity_type_id, $bundle, $schema_type);
    return $defaults;
  }

}
