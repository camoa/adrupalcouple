<?php

namespace Drupal\Tests\brevo\Functional;

use Drupal\brevo\BrevoHandlerInterface;
use Drupal\Core\Url;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group brevo
 */
class BrevoAdminSettingsFormTest extends BrevoFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['brevo', 'brevo_test'];

  /**
   * Tests admin pages provided by Brevo.
   */
  public function testSettingsFormSubmit() {
    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('brevo.admin_settings_form'));

    // Make sure that "API Key" field is visible and required.
    $api_key_field = $this->assertSession()->elementExists('css', 'input[name="api_key"]');
    $this->assertTrue($api_key_field->hasAttribute('required'));

    // Test invalid value for API key.
    $this->submitSettingsForm(['api_key' => 'invalid_value'], "Couldn't connect to the Brevo API. Please check your API settings.");

    // Test valid but not working API key.
    $this->submitSettingsForm(['api_key' => 'xkeysib-abkjwg4wpabh68ErgX4fd4i1Brv63Zum91C6mxyo8bljOhC7gnoACQKKKOxn2Sjd-AbzqOjdWSIOwjYNw'], "Couldn't connect to the Brevo API. Please check your API settings.");

    // Test valid and working API key.
    $this->submitSettingsForm(['api_key' => 'xkeysib-cukjwg4wpabh68ErgX4fd4i1Brv63Zum91C6mxyo8bljOhC7gnoACQKKKOxn2Sjd-XzzqOjdWSIOwjYNw'], 'The configuration options have been saved.');

    // Save additional parameters. Check that all fields available on the form.
    $field_values = [
      'api_key' => 'xkeysib-cukjwg4wpabh68ErgX4fd4i1Brv63Zum91C6mxyo8bljOhC7gnoACQKKKOxn2Sjd-XzzqOjdWSIOwjYNw',
    ];
    $this->submitSettingsForm($field_values, 'The configuration options have been saved.');

    // Rebuild config values after form submit.
    $this->brevoConfig = $this->config(BrevoHandlerInterface::CONFIG_NAME);

    // Test that all field values are stored in configuration.
    foreach ($field_values as $field_name => $field_value) {
      $this->assertEquals($field_value, $this->brevoConfig->get($field_name));
    }
  }

  /**
   * Submits Brevo settings form with given values and checks status message.
   */
  private function submitSettingsForm(array $values, $result_message) {
    foreach ($values as $field_name => $field_value) {
      $this->getSession()->getPage()->fillField($field_name, $field_value);
    }
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains($result_message);
  }

}
