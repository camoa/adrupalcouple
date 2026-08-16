<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests markdown_easy post-update hooks.
 *
 * @group markdown_easy
 */
class PostUpdateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['markdown_easy', 'system'];

  /**
   * Tests the post-update hook creates expected config.
   */
  public function testPostUpdate20000(): void {
    // Remove the config manually if it already exists.
    $config_storage = \Drupal::service('config.storage');
    $config_storage->delete('markdown_easy.settings');

    // Confirm it doesn't exist yet.
    $this->assertNull(\Drupal::config('markdown_easy.settings')->get('skip_filter_enforcement'));

    // Include the post_update file manually.
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('markdown_easy')->getPath();
    require_once DRUPAL_ROOT . '/' . $module_path . '/markdown_easy.post_update.php';

    // Run the update hook.
    $message = markdown_easy_post_update_20000();

    // Re-fetch the config to test the result.
    $config = \Drupal::config('markdown_easy.settings');

    $this->assertEquals(FALSE, $config->get('skip_filter_enforcement'));
    $this->assertSame('markdown_easy.settings configuration imported from default settings.', (string) $message);
  }

}
