<?php

namespace Drupal\Tests\brevo_mailer\Functional;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that all provided admin pages are reachable.
 *
 * @group brevo
 */
class BrevoUiPageTest extends BrevoFunctionalTestBase {

  /**
   * List of Brevo admin routes.
   *
   * @var array
   */
  private $adminPages = [
    'brevo_mailer.admin_settings_form',
    'brevo_mailer.test_email_form',
  ];

  /**
   * Tests admin pages provided by Brevo Mailer.
   */
  public function testAdminPages() {
    $admin_user = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($admin_user);

    // User with 'administer brevo' permission should have access.
    $this->checkRoutesStatusCode(Response::HTTP_OK);

    $this->drupalLogout();

    $common_user = $this->drupalCreateUser();
    $this->drupalLogin($common_user);

    // User without 'administer brevo' permission shouldn't have access.
    $this->checkRoutesStatusCode(Response::HTTP_FORBIDDEN);
  }

  /**
   * Helper. Checks status codes on admin routes by current user.
   */
  private function checkRoutesStatusCode($status_code) {
    foreach ($this->adminPages as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $this->assertSession()->statusCodeEquals($status_code);
    }
  }

}
