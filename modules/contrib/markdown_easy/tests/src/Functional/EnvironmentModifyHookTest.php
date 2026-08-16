<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Test that the Markdown processor config is overridable.
 *
 * @group markdown_easy
 */
final class EnvironmentModifyHookTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'markdown_easy_hook_test',
    'node',
    'field',
    'filter',
    'text',
  ];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);

    $test_standard_markdown_format = FilterFormat::create([
      'format' => 'test_standard_markdown',
      'name' => 'Test Standard Markdown format',
      'weight' => 1,
      'filters' => [
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'standard',
          ],
        ],
      ],
    ]);
    $test_standard_markdown_format->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 1,
      'type' => 'article',
      'body' => [
        'value' => "I've made a huge mistake. ~~This is struck.~~ There’s always money in the banana stand.",
        'format' => 'test_standard_markdown',
      ],
    ])->save();
  }

  /**
   * Test that the environment can be overridden.
   *
   * @test
   */
  public function testAddMarkdownExtension(): void {
    $session = $this->assertSession();
    $this->drupalGet('/node/1');
    $session->statusCodeEquals(200);
    $session->elementExists('css', 'del:contains("This is struck.")');
  }

}
