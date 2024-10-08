<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Wizard;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Views UI wizard tests.
 */
abstract class WizardTestBase extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_ui', 'block', 'rest'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    // Create and log in a user with administer views permission.
    $views_admin = $this->drupalCreateUser([
      'administer views',
      'administer blocks',
      'bypass node access',
      'access user profiles',
      'view all revisions',
    ]);
    $this->drupalLogin($views_admin);
    $this->drupalPlaceBlock('local_actions_block');
  }

}
