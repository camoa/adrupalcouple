<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the functionality of the Schema.org autocomplete element.
 *
 * @see \Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgAutocompleteControllerTest
 * @covers \Drupal\schemadotorg\Element\SchemaDotOrgAutocomplete
 * @group schemadotorg
 */
class SchemaDotOrgAutocompleteElementTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_autocomplete_element_test'];

  /**
   * Test Schema.org autocomplete form.
   */
  public function testSchemaDotOrgSettingsElement(): void {
    $assert_session = $this->assertSession();

    // Check autocomplete submitted values.
    $this->drupalGet('/schemadotorg-autocomplete-element-test');
    $this->submitForm([], 'Submit');
    $expected_data = "schemadotorg_autocomplete_type: Person
schemadotorg_autocomplete_types:
  - Person
  - Organization
schemadotorg_autocomplete_novalidate: Dog
schemadotorg_autocomplete_thing: Thing
schemadotorg_autocomplete_property: name
schemadotorg_autocomplete_properties:
  - name
  - additionalName
schemadotorg_autocomplete_action_path: ''
schemadotorg_autocomplete_action_query: ''";
    $assert_session->responseContains($expected_data);

    // Check autocomplete Schema.org type validation.
    $this->drupalGet('/schemadotorg-autocomplete-element-test');
    $edit = [
      'schemadotorg_autocomplete_type' => 'Cat',
      'schemadotorg_autocomplete_property' => 'paws',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The Schema.org type <em class="placeholder">Cat</em> is not valid.');
    $assert_session->responseContains('The Schema.org property <em class="placeholder">paws</em> is not valid.');

    // Check autocomplete Schema.org Thing validation.
    $this->drupalGet('/schemadotorg-autocomplete-element-test');
    $edit = [
      'schemadotorg_autocomplete_thing' => 'Enumeration',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The Schema.org type <em class="placeholder">Enumeration</em> is not a valid <em class="placeholder">Thing</em>.');
  }

}
