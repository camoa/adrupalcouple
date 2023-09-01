<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Functional;

/**
 * Tests the functionality of the Schema.org settings element.
 *
 * @covers \Drupal\schemadotorg\Element\SchemaDotOrgSettings
 * @group schemadotorg
 */
class SchemaDotOrgSettingsElementTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg_settings_element_test'];

  /**
   * Test Schema.org settings form.
   */
  public function testSchemaDotOrgSettingsElement(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/schemadotorg-settings-element-test');

    // Check that invalid settings render as YAML and display a warning message.
    $assert_session->responseContains('<textarea wrap="off" data-drupal-selector="edit-schemadotorg-settings-element-test-associative-grouped-invalid" class="schemadotorg-codemirror form-textarea" data-mode="yaml" id="edit-schemadotorg-settings-element-test-associative-grouped-invalid" name="schemadotorg_settings_element_test[associative_grouped_invalid]" rows="5" cols="60">');
    $assert_session->responseContains('<strong>Unable to parse <em class="placeholder">associative_grouped_invalid</em> settings.</strong>');

    // Check expected values when submitting the form via text format.
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[indexed]', 'one
two
three');
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[yaml]', 'title: YAML');
    $this->submitForm([], 'Submit');
    $expected_data = "indexed:
  - one
  - two
  - three
indexed_grouped:
  A:
    - one
    - two
    - three
  B:
    - four
    - five
    - six
indexed_grouped_named:
  A:
    label: 'Group A'
    items:
      - one
      - two
      - three
  B:
    label: 'Group B'
    items:
      - four
      - five
      - six
associative:
  one: One
  two: Two
  three: Three
associative_grouped:
  A:
    one: One
    two: Two
    three: Three
  B:
    four: Four
    five: Five
    six: Six
associative_grouped_named:
  A:
    label: 'Group A'
    items:
      one: One
      two: Two
      three: Three
  B:
    label: 'Group B'
    items:
      four: Four
      five: Five
      six: Six
links:
  -
    title: Yahoo!!!
    uri: 'https://yahoo.com'
  -
    title: Google
    uri: 'https://google.com'
links_grouped:
  A:
    -
      title: Yahoo!!!
      uri: 'https://yahoo.com'
  B:
    -
      title: Google
      uri: 'https://google.com'
yaml:
  title: YAML
associative_advanced:
  title: Title
  required: true
  height: 100
  width: 100
associative_grouped_invalid:
  A:
    one: 'One,comma'
    two: Two
    three: Three";
    $assert_session->responseContains($expected_data);

    // Check expected values when submitting the form via text format.
    $this->drupalGet('/schemadotorg-settings-element-test', ['query' => ['yaml' => 1]]);
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[indexed]', '- one
- two
- three');
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[yaml]', 'title: YAML');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains($expected_data);

    // Check show YAML state is stored in the user state.
    $this->drupalGet('/schemadotorg-settings-element-test');
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[indexed]', '- one
- two
- three');
    $this->drupalGet('/schemadotorg-settings-element-test', ['query' => ['yaml' => 0]]);
    $assert_session->fieldValueEquals('schemadotorg_settings_element_test[indexed]', 'one
two
three');

    // Check YAML validation.
    $this->drupalGet('/schemadotorg-settings-element-test', ['query' => ['yaml' => 1]]);
    $this->submitForm(['schemadotorg_settings_element_test[indexed]' => '"not: valid yaml'], 'Submit');
    $assert_session->responseContains('Error message');

    // Check configuration Schema.org validation.
    $this->drupalGet('/schemadotorg-settings-element-test', ['query' => ['yaml' => 1]]);
    $this->submitForm(['schemadotorg_settings_element_test[indexed]' => 'not: [valid schema]'], 'Submit');
    $assert_session->responseContains('indexed field is invalid.');
    $assert_session->responseContains('The configuration property indexed.not.0 doesn&#039;t exist.');
  }

}
