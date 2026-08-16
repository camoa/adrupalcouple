<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\filter\Entity\FilterFormat;

/**
 * Basic filter tests.
 *
 * @group markdown_easy
 */
final class FilterTest extends BrowserTestBase {

  protected const TEST_TEXT = <<<EOF
    This has a footnote.[^1]

    **This should be strong.** _This is emphasized._ ~This is struck.~

    [^1]: This is the footnote.

    Orange
    :   The fruit of an <em>evergreen tree</em> of the genus Citrus.

    | Left-aligned | Center-aligned | Right-aligned |
    | :---         |     :---:      |          ---: |
    | git status   | git status     | git status    |
    | git diff     | git diff       | git diff      |
    | git left     | git center     | git right     |
  EOF;

  // Allow the same list of tags for all filter types.
  protected const ALLOWED_HTML = "<strong> <em> <del> <p> <br> <a href> <dl> <dt> <dd> <table> <thead> <tbody> <tr> <th class> <td class> <h2>";

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

    $test_standard_markdown_format = FilterFormat::create([
      'format' => 'test_standard_markdown',
      'name' => 'Test Standard Markdown format',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 2,
          'settings' => [
            'allowed_html' => self::ALLOWED_HTML,
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
    $test_standard_markdown_format->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 1,
      'type' => 'article',
      'body' => [
        'value' => self::TEST_TEXT,
        'format' => 'test_standard_markdown',
      ],
    ])->save();

    $test_github_markdown_format = FilterFormat::create([
      'format' => 'test_github_markdown',
      'name' => 'Test GitHub Markdown format',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 2,
          'settings' => [
            'allowed_html' => self::ALLOWED_HTML,
          ],
        ],
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'github',
          ],
        ],
      ],
    ]);
    $test_github_markdown_format->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 2,
      'type' => 'article',
      'body' => [
        'value' => self::TEST_TEXT,
        'format' => 'test_github_markdown',
      ],
    ])->save();

    $test_markdownsmorgasbord_format = FilterFormat::create([
      'format' => 'test_markdownsmorgasbord',
      'name' => 'Test Markdown Smörgåsbord format',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 2,
          'settings' => [
            'allowed_html' => self::ALLOWED_HTML,
          ],
        ],
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'markdownsmorgasbord',
          ],
        ],
      ],
    ]);
    $test_markdownsmorgasbord_format->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 3,
      'type' => 'article',
      'body' => [
        'value' => self::TEST_TEXT,
        'format' => 'test_markdownsmorgasbord',
      ],
    ])->save();

    // Format that uses only the Markdown Easy filter.
    $config = $this->config('markdown_easy.settings');
    $config->set('skip_filter_enforcement', 1);
    $config->save();
    $test_markdown_easy_only_format = FilterFormat::create([
      'format' => 'test_markdown_easy_only',
      'name' => 'Test Markdown Easy only format',
      'weight' => 1,
      'filters' => [
        'markdown_easy' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'flavor' => 'github',
          ],
        ],
      ],
    ]);
    $test_markdown_easy_only_format->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 4,
      'type' => 'article',
      'body' => [
        'value' => self::TEST_TEXT,
        'format' => 'test_markdown_easy_only',
      ],
    ])->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 5,
      'type' => 'article',
      'body' => [
        'value' => "## This is a heading\n\nThis is a paragraph\nthat spans multiple\nlines.\n\nThis paragraph  \ncontains a line break.",
        'format' => 'test_github_markdown',
      ],
    ])->save();
  }

  /**
   * Test the most basic functionality.
   *
   * @test
   */
  public function testFlavors(): void {
    $session = $this->assertSession();

    // Standard Markdown.
    $this->drupalGet('/node/1');
    $session->statusCodeEquals(200);
    // Tables not supported in standard markdown, so table CSS not included.
    $session->responseNotContains('/css/theme/markdown-table.css');
    $session->elementExists('css', 'strong:contains("This should be strong.")');
    $session->elementExists('css', 'em:contains("This is emphasized.")');
    $session->elementNotExists('css', 'del:contains("This is struck.")');
    $session->elementNotExists('xpath', '//a[@href="#fn_1" and text()="1"]');
    $session->elementNotExists('xpath', '//a[@href="#fnref_1" and text()="↩"]');
    $session->elementNotExists('xpath', '//dt[text()="Orange"]');
    $session->elementNotExists('xpath', '//dd[text()="The fruit of an evergreen tree of the genus Citrus."]');
    $session->elementNotExists('xpath', '//th[text()="Left-aligned"]');
    $session->elementNotExists('xpath', '//th[text()="Center-aligned"]');
    $session->elementNotExists('xpath', '//th[text()="Right-aligned"]');
    $session->elementNotExists('xpath', '//td[text()="git left"]');
    $session->elementNotExists('xpath', '//td[text()="git center"]');
    $session->elementNotExists('xpath', '//td[text()="git right"]');

    // GitHub Markdown.
    $this->drupalGet('/node/2');
    $session->statusCodeEquals(200);
    $session->responseContains('/css/theme/markdown-table.css');
    $session->elementExists('css', 'strong:contains("This should be strong.")');
    $session->elementExists('css', 'em:contains("This is emphasized.")');
    $session->elementExists('css', 'del:contains("This is struck.")');
    $session->elementNotExists('xpath', '//a[@href="#fn_1" and text()="1"]');
    $session->elementNotExists('xpath', '//a[@href="#fnref_1" and text()="↩"]');
    $session->elementNotExists('xpath', '//dt[text()="Orange"]');
    $session->elementNotExists('xpath', '//dd[text()="The fruit of an evergreen tree of the genus Citrus."]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-left") and text()="Left-aligned"]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-center") and text()="Center-aligned"]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-right") and text()="Right-aligned"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-left") and text()="git left"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-center") and text()="git center"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-right") and text()="git right"]');

    // Markdown Smörgåsbord.
    $this->drupalGet('/node/3');
    $session->statusCodeEquals(200);
    $session->responseContains('/css/theme/markdown-table.css');
    $session->elementExists('css', 'strong:contains("This should be strong.")');
    $session->elementExists('css', 'em:contains("This is emphasized.")');
    $session->elementExists('css', 'del:contains("This is struck.")');
    $session->elementExists('xpath', '//a[@href="#fn_1" and text()="1"]');
    $session->elementExists('xpath', '//a[@href="#fnref_1" and text()="↩"]');
    $session->elementExists('xpath', '//dt[text()="Orange"]');
    $session->elementExists('xpath', '//dd[text()="The fruit of an evergreen tree of the genus Citrus."]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-left") and text()="Left-aligned"]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-center") and text()="Center-aligned"]');
    $session->elementExists('xpath', '//th[contains(@class, "markdown-align-right") and text()="Right-aligned"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-left") and text()="git left"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-center") and text()="git center"]');
    $session->elementExists('xpath', '//td[contains(@class, "markdown-align-right") and text()="git right"]');
  }

  /**
   * Test the skip_html_input_stripping config option.
   *
   * @test
   */
  public function testSkipHtmlInputStripping(): void {
    $session = $this->assertSession();

    $this->drupalGet('/node/4');
    $session->statusCodeEquals(200);
    $session->elementNotExists('css', 'em:contains("evergreen tree")');

    $config = $this->config('markdown_easy.settings');
    $config->set('skip_html_input_stripping', 1);
    $config->save();

    // Reload and re-save the format to use the updated config.
    $format = FilterFormat::load('test_markdown_easy_only');
    $format->save();

    $this->drupalGet('/node/4');
    $session->statusCodeEquals(200);
    $session->elementExists('css', 'em:contains("evergreen tree")');
  }

  /**
   * Test that line breaks in paragraphs are handled correctly.
   *
   * @test
   */
  public function testParagraphs(): void {
    $session = $this->assertSession();
    $this->drupalGet('/node/5');
    $session->statusCodeEquals(200);
    $session->elementExists('css', 'h2:contains("This is a heading")');
    // Verify can't check first paragraph without normalize.
    $session->elementNotExists('xpath', '//div/p[contains(text(), "This is a paragraph that spans multiple lines.")]');
    // Verify first paragraph can be checked by normalize.
    $session->elementExists('xpath', '//div/p[.//text()[normalize-space() = "This is a paragraph that spans multiple lines."]]');
    // Verify first paragraph does not contain any line breaks.
    $session->elementNotExists('xpath', '//div/p[contains(text(), "This is a paragraph")]/br');
    // Verify that second paragraph won't normalize because of <br>.
    $session->elementNotExists('xpath', '//div/p[.//text()[normalize-space() = "This paragraph contains a line break."]]');
    // Verify that second paragraph contains a line <br> tag.
    $session->elementExists('xpath', '//div/p[contains(text(),"This paragraph")]/br');
  }

}
