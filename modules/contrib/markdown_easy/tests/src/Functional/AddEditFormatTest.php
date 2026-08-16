<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test Markdown Easy warnings when adding or editing a text format.
 *
 * @group markdown_easy
 */
class AddEditFormatTest extends BrowserTestBase {

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
   * Test string constants.
   */
  protected const REQUIRED_TAGS_FOR_FLAVOR = 'For full compatibility with the selected Markdown flavor, add the following tags and attributes to the "Limit allowed HTML tags and correct faulty HTML" filter: ';
  protected const REQUIRED_TAGS_FOR_STANDARD = self::REQUIRED_TAGS_FOR_FLAVOR . '<p> <a href title> <img alt src title> <pre> <h1> <hr> <br>';
  protected const REQUIRED_TAGS_FOR_GITHUB = self::REQUIRED_TAGS_FOR_FLAVOR . '<p> <a href title> <img alt src title> <pre> <h1> <hr> <br> <del> <table> <thead> <tbody> <tr> <th class> <td class> <input checked disabled type';
  protected const REQUIRED_TAGS_FOR_SMORGASBORD = self::REQUIRED_TAGS_FOR_FLAVOR . '<p> <a class href role title> <img alt src title> <pre> <li class id role> <h1> <hr> <br> <del> <table> <thead> <tbody> <tr> <th class> <td class> <input checked disabled type> <sup id>';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test various warning messages on format page when using Markdown Easy.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAddEditFormat(): void {
    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to the add text format page.
    $this->drupalGet('/admin/config/content/formats/add');
    $session->statusCodeEquals(200);

    // Populate the new text format form (tests "add" mode).
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][settings][flavor]' => 'standard',
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The Markdown Easy filter needs to be placed before the Limit allowed HTML tags and correct faulty HTML filter');

    // Resubmit the text format with additional filters enabled, but in a
    // potentially insecure order.
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][weight]' => 30,
      'filters[markdown_easy][settings][flavor]' => 'standard',
      'filters[filter_html][status]' => 1,
      'filters[filter_html][weight]' => 20,
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The Markdown Easy filter needs to be placed before the Limit allowed HTML tags and correct faulty HTML filter');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_STANDARD);

    // Resubmit the text format with filters in a proper order.
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][weight]' => 10,
      'filters[markdown_easy][settings][flavor]' => 'standard',
      'filters[filter_html][status]' => 1,
      'filters[filter_html][weight]' => 20,
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('Added text format Markdown Easy format page test.');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_STANDARD);

    // Navigate back to the edit text format page (tests "edit" mode).
    $this->drupalGet('/admin/config/content/formats/manage/markdown_easy_format_page_test');
    $session->statusCodeEquals(200);
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The text format Markdown Easy format page test has been updated.');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_STANDARD);

    // Resubmit the text format with additional filters enabled, but in a
    // potentially insecure order.
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][weight]' => 30,
      'filters[markdown_easy][settings][flavor]' => 'standard',
      'filters[filter_html][status]' => 1,
      'filters[filter_html][weight]' => 20,
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The Markdown Easy filter needs to be placed before the Limit allowed HTML tags and correct faulty HTML filter');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_STANDARD);

    // Test skipping validation via config.
    $config = $this->config('markdown_easy.settings');
    $config->set('skip_filter_enforcement', 1);
    $config->save();
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('The Markdown Easy filter needs to be placed before the Limit allowed HTML tags and correct faulty HTML filter');

    // Unset skipping validation.
    $config = $this->config('markdown_easy.settings');
    $config->set('skip_filter_enforcement', 0);
    $config->save();

    // Resubmit the text format with filters in a proper order, different
    // Markdown Easy flavor of 'github'.
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][weight]' => 10,
      'filters[markdown_easy][settings][flavor]' => 'github',
      'filters[filter_html][status]' => 1,
      'filters[filter_html][weight]' => 20,
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The text format Markdown Easy format page test has been updated.');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_GITHUB);

    // Resubmit the text format with filters in a proper order, different
    // Markdown Easy flavor of 'markdownsmorgasbord'.
    $edit = [
      'name' => 'Markdown Easy format page test',
      'format' => 'markdown_easy_format_page_test',
      'filters[markdown_easy][status]' => 1,
      'filters[markdown_easy][weight]' => 10,
      'filters[markdown_easy][settings][flavor]' => 'markdownsmorgasbord',
      'filters[filter_html][status]' => 1,
      'filters[filter_html][weight]' => 20,
    ];
    $this->submitForm($edit, 'Save configuration');
    $session->statusCodeEquals(200);
    $session->pageTextContainsOnce('The text format Markdown Easy format page test has been updated.');
    $session->pageTextContainsOnce(self::REQUIRED_TAGS_FOR_SMORGASBORD);
  }

  /**
   * Tests that Markdown Easy filter tips are hidden when skipping validation.
   *
   * @test
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testTips(): void {
    // Start the browsing session.
    $session = $this->assertSession();

    // Navigate to the add text format page.
    $this->drupalGet('/admin/config/content/formats/add');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The Markdown Easy filter should run before the "Limit allowed HTML tags and correct faulty HTML" filter. It is required to use these filters together.');

    // Test skipping validation via config.
    $config = $this->config('markdown_easy.settings');
    $config->set('skip_filter_enforcement', 1);
    $config->save();
    $this->drupalGet('/admin/config/content/formats/add');
    $session->statusCodeEquals(200);
    $session->pageTextNotContains('The Markdown Easy filter should run before the "Limit allowed HTML tags and correct faulty HTML" filter. It is required to use these filters together.');
  }

}
