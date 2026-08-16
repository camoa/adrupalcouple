<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Filter warning tests.
 *
 * @group markdown_easy
 */
final class FilterWarningsTest extends BrowserTestBase {

  protected const FILTER_HTML_WARNING_XPATH = '//div[contains(@class, "system-status-report__entry__value")]/div[contains(@class, "description") and contains(., \'The "Limit allowed HTML tags and correct faulty HTML" filter is required and should be configured to run after the Markdown Easy filter\')]';
  protected const CONVERT_LINE_WARNING_XPATH = '//div[contains(@class, "system-status-report__entry__value")]/div[contains(@class, "description") and contains(., \'The "Convert line breaks into HTML" filter is not recommended when using Markdown Easy filter\')]';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'markdown_easy',
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
  }

  /**
   * Test that the Status Report shows the Markdown Easy warning.
   *
   * @test
   */
  public function testSecureStatusReport(): void {

    $test_securely_configured_format = FilterFormat::create([
      'format' => 'securely_configured_format',
      'name' => 'Secure Markdown format',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 2,
          'settings' => [
            'allowed_html' => "<strong> <em> <del>",
          ],
        ],
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'standard',
          ],
        ],
      ],
    ]);
    $test_securely_configured_format->save();

    $session = $this->assertSession();
    $this->drupalGet('/admin/reports/status');
    $session->statusCodeEquals(200);
    $session->elementNotExists('xpath', self::FILTER_HTML_WARNING_XPATH);
    $session->elementNotExists('xpath', self::CONVERT_LINE_WARNING_XPATH);
  }

  /**
   * Test that the Status Report shows the Markdown Easy warning.
   *
   * @test
   */
  public function testInsecureStatusReport(): void {

    $test_securely_configured_format = FilterFormat::create([
      'format' => 'securely_configured_format',
      'name' => 'Secure Markdown format',
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
    $test_securely_configured_format->save();

    $session = $this->assertSession();
    $this->drupalGet('/admin/reports/status');
    $session->statusCodeEquals(200);
    $session->elementExists('xpath', self::FILTER_HTML_WARNING_XPATH);
    $session->elementNotExists('xpath', self::CONVERT_LINE_WARNING_XPATH);
  }

  /**
   * Test that the Status Report shows the Markdown Easy warning.
   *
   * @test
   */
  public function testLinebreakStatusReport(): void {

    $test_securely_configured_format = FilterFormat::create([
      'format' => 'securely_configured_format',
      'name' => 'Secure Markdown format',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 1,
          'settings' => [
            'allowed_html' => "<strong> <em> <del>",
          ],
        ],
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'standard',
          ],
        ],
      ],
    ]);
    $test_securely_configured_format->save();

    $session = $this->assertSession();
    $this->drupalGet('/admin/reports/status');
    $session->statusCodeEquals(200);
    $session->elementNotExists('xpath', self::FILTER_HTML_WARNING_XPATH);
  }

  /**
   * Test that the Status Report when skipping filter enforcement.
   *
   * @test
   */
  public function testStatusReportWithSkipFilterEnforcement(): void {
    $config = $this->config('markdown_easy.settings');
    $config->set('skip_filter_enforcement', 1);
    $config->save();

    $this->drupalGet('/admin/reports/status');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('The "Limit allowed HTML tags and correct faulty HTML" filter is required and should be configured to run after the Markdown Easy filter.');
  }

}
