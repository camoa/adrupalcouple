<?php

namespace Drupal\Tests\brevo_mailer\Functional;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\Url;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group brevo
 */
class BrevoMailerAdminSettingsFormTest extends BrevoFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['brevo', 'brevo_mailer'];

  /**
   * Tests admin pages provided by Brevo.
   */
  public function testSettingsFormSubmit() {
    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet(Url::fromRoute('brevo_mailer.admin_settings_form'));

    // Make sure that core fields are visible.
    // Note: use_queue field only appears when mailsystem module is installed.
    $this->assertSession()->elementExists('css', 'input[name="debug_mode"]');
    $this->assertSession()->elementExists('css', 'input[name="test_mode"]');
    $this->assertSession()->elementExists('css', 'input[name="use_theme"]');

    // Check that all fields available on the form.
    $field_values = [
      'debug_mode' => TRUE,
      'test_mode' => TRUE,
      'use_theme' => FALSE,
    ];
    $this->submitSettingsForm($field_values, 'The configuration options have been saved.');

    // Rebuild config values after form submit.
    $this->brevoMailerConfig = $this->config(BrevoMailerHandlerInterface::CONFIG_NAME);

    // Test that all field values are stored in configuration.
    foreach ($field_values as $field_name => $field_value) {
      $this->assertEquals($field_value, $this->brevoMailerConfig->get($field_name));
    }
  }

  /**
   * Submits Brevo Mailer settings form with given values.
   */
  private function submitSettingsForm(array $values, $result_message) {
    foreach ($values as $field_name => $field_value) {
      $this->getSession()->getPage()->fillField($field_name, $field_value);
    }
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains($result_message);
  }

}
