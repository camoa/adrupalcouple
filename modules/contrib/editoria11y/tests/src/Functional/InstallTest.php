<?php

namespace Drupal\Tests\editoria11y\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Basic test to confirm the module installs OK.
 *
 * @group editoria11y
 */
class InstallTest extends BrowserTestBase {
  /**
   * {@inheritDoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editoria11y', 'user', 'views'];

  /**
   * Basic test to make sure we can access the configuration page.
   */
  public function testConfigurationPage() {

    $user = $this->setUpAdmin();
    $route = Url::fromRoute("editoria11y.settings");

    $this->drupalLogin($user);
    $this->drupalGet($route);
    $this->assertSession()->statusCodeEquals("200");
    $this->assertSession()->pageTextContains("Editoria11y Settings");
    $this->drupalLogout();
  }

  /**
   * Define a new administrator user.
   */
  public function setUpAdmin() : AccountInterface {
    return $this->createUser([
      'administer editoria11y checker',
      'view editoria11y checker',
    ]
    );
  }

}
