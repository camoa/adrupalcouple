<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\markdown_easy\MarkdownUtility;

/**
 * Tests the MarkdownUtility class.
 *
 * @group markdown_easy
 */
class MarkdownUtilityTest extends UnitTestCase {

  /**
   * Data provider for testing the getFilterWeights method.
   *
   * @return array<int, array<int, array<string, mixed>|null>>
   *   An array of test cases.
   */
  public static function filterWeightsDataProvider(): array {
    return [
      [
        [
          'filter_html' => ['status' => FALSE],
          'markdown_easy' => ['status' => FALSE],
        ],
        [
          'filter_html' => NULL,
          'markdown_easy' => NULL,
        ],
      ],
      [
        [
          'filter_html' => ['status' => TRUE, 'weight' => 10],
          'markdown_easy' => ['status' => FALSE],
        ],
        [
          'filter_html' => 10,
          'markdown_easy' => NULL,
        ],
      ],
      [
        [
          'filter_html' => ['status' => TRUE, 'weight' => 10],
          'markdown_easy' => ['status' => TRUE, 'weight' => 30],
        ],
        [
          'filter_html' => 10,
          'markdown_easy' => 30,
        ],
      ],
    ];
  }

  /**
   * Tests the getFilterWeights method.
   *
   * @param array<int, array<string, mixed>|null> $filters
   *   The list of filters enabled on the text format.
   * @param array<int, array<string, mixed>|null> $expectedWeights
   *   The expected weights for the filters we care about.
   *
   * @dataProvider filterWeightsDataProvider
   * @test
   */
  public function testGetFilterWeights(array $filters, array $expectedWeights): void {
    $markdownUtility = new MarkdownUtility();
    $this::assertEquals($expectedWeights, $markdownUtility->getFilterWeights($filters));
  }

  /**
   * Data provider for testing the findMissingTags method.
   *
   * @return array<array<string, string|array<string, list<string>>>>
   *   An array of test cases.
   */
  public static function findMissingTagsDataProvider(): array {
    return [
      [
        'flavor' => 'standard',
        'allowed_html' => "<img src alt title> <pre> <hr> <a title href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>",
        'expected' => [
          'p' => [],
          'br' => [],
        ],
      ],
      [
        'flavor' => 'github',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup id> <del> <table> <thead> <tbody> <th class> <td class> <input type checked disabled>",
        'expected' => ['tr' => []],
      ],
      [
        'flavor' => 'github',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup id> <del> <table> <thead> <tbody> <tr> <td class> <input type checked disabled>",
        'expected' => ['th' => ['class']],
      ],
      [
        'flavor' => 'github',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup id> <del> <table> <thead> <tbody> <th> <tr> <td class> <input type checked disabled>",
        'expected' => ['th' => ['class']],
      ],
      [
        'flavor' => 'github',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup id> <del> <table> <thead> <tbody> <th class> <tr> <td class> <input type checked disabled>",
        'expected' => [],
      ],
      [
        'flavor' => 'markdownsmorgasbord',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class role> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup> <li class id role>",
        'expected' => [
          'sup' => ['id'],
          'del' => [],
          'table' => [],
          'thead' => [],
          'tbody' => [],
          'tr' => [],
          'th' => ['class'],
          'td' => ['class'],
          'input' => ['checked', 'disabled', 'type'],
        ],
      ],
      [
        'flavor' => 'markdownsmorgasbord',
        'allowed_html' => "<a href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id class role> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id>",
        'expected' => [
          'img' => ['alt', 'src', 'title'],
          'a' => ['class', 'href', 'role', 'title'],
          'p' => [],
          'br' => [],
          'h1' => [],
          'hr' => [],
          'pre' => [],
          'sup' => ['id'],
          'dl' => [],
          'del' => [],
          'table' => [],
          'thead' => [],
          'tbody' => [],
          'tr' => [],
          'th' => ['class'],
          'td' => ['class'],
          'input' => ['checked', 'disabled', 'type'],
        ],
      ],
      [
        'flavor' => 'markdownsmorgasbord',
        'allowed_html' => "<a href hreflang class> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id class role> <dt> <dd> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> ",
        'expected' => [
          'img' => ['alt', 'src', 'title'],
          'a' => ['class', 'href', 'role', 'title'],
          'p' => [],
          'br' => [],
          'h1' => [],
          'hr' => [],
          'pre' => [],
          'sup' => ['id'],
          'dl' => [],
          'del' => [],
          'table' => [],
          'thead' => [],
          'tbody' => [],
          'tr' => [],
          'th' => ['class'],
          'td' => ['class'],
          'input' => ['checked', 'disabled', 'type'],
        ],
      ],
      [
        'flavor' => 'markdownsmorgasbord',
        'allowed_html' => "<p> <br> <img src alt title> <pre> <hr> <a title href hreflang class role> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type='1 A I'> <li id> <dl> <dt> <dd> <h1> <h2 id='jump-*'> <h3 id> <h4 id> <h5 id> <h6 id> <sup id> <li class id role> <del> <table> <thead> <tbody> <tr> <th class> <td class> <input checked disabled type>",
        'expected' => [],
      ],
    ];
  }

  /**
   * Tests the findMissingTags method.
   *
   * @param string $flavor
   *   Name of the Markdown Easy flavor being used.
   * @param string $allowed_html
   *   Space-separated list of HTML tags and attributes allowed.
   * @param array<int, array<string, string>|null> $expected
   *   The expected missing tags.
   *
   * @dataProvider findMissingTagsDataProvider
   * @test
   */
  public function testFindMissingTags(string $flavor, string $allowed_html, array $expected): void {
    $markdownUtility = new MarkdownUtility();
    $this::assertEquals($expected, $markdownUtility->findMissingTags($flavor, $allowed_html));
  }

  /**
   * Data provider for testing the formatHtmlTags method.
   *
   * @return array<int, array<string, array<string, list<string>>|string>>
   *   An array of test cases.
   */
  public static function formatHtmlTagsDataProvider(): array {
    return [
      [
        'reported' => [
          'p' => [],
          'br' => [],
        ],
        'expected' => "<p> <br>",
      ],
      [
        'reported' => [
          'a' => ['role'],
          'img' => ['role', 'title'],
        ],
        'expected' => "<a role> <img role title>",
      ],
      [
        'reported' => [
          'a' => ['class', 'href', 'role', 'title'],
          'img' => ['alt', 'src', 'title'],
          'li' => ['class', 'id', 'role'],
          'p' => [],
          'sup' => ['id'],
        ],
        'expected' => "<a class href role title> <img alt src title> <li class id role> <p> <sup id>",
      ],
    ];
  }

  /**
   * Tests the formatHtmlTags method.
   *
   * @param array<string, array<string, string>|null> $reported
   *   The reported missing tags.
   * @param string $expected
   *   Space-separated list of HTML tags and attributes missing.
   *
   * @dataProvider formatHtmlTagsDataProvider
   * @test
   */
  public function testFormatHtmlTags(array $reported, string $expected): void {
    $markdownUtility = new MarkdownUtility();
    $this::assertEquals($expected, $markdownUtility->formatHtmlTags($reported));
  }

}
