<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Utility;

/**
 * Helper class Schema.org html methods.
 */
class SchemaDotOrgHtmlHelper {

  /**
   * Convert Markdowm to HTML.
   *
   * @param string $markdown
   *   A string containing Markdown.
   *
   * @return string
   *   A string containing Markdown converted to HTMl.
   */
  public static function fromMarkdown(string $markdown): string {
    // Remove the table of contents.
    $markdown = preg_replace('/^.*?(Introduction\s+------------)/s', '$1', $markdown);

    if (!class_exists('\Michelf\Markdown')) {
      return '<pre>' . $markdown . '</pre>';
    }

    // phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
    $html = \Michelf\Markdown::defaultTransform($markdown);

    // Remove <p> tags with <li> tags.
    $html = preg_replace('#<li>\s*<p>#m', '<li>', $html);
    $html = preg_replace('#</p>\s*</li>#m', '</li>', $html);

    // Convert <p><code> tags to <pre> tags.
    $html = preg_replace('#<p>\s*<code>#m', '<pre>', $html);
    $html = preg_replace('#</code>\s*</p>#m', '</pre>', $html);

    // Convert <pre><code> tags to <pre> tags.
    $html = preg_replace('#<pre>\s*<code>#m', '<pre>', $html);
    $html = preg_replace('#</code>\s*</pre>#m', '</pre>', $html);

    // Create fake filter object with filter URL settings.
    if (function_exists('_filter_url')) {
      $filter = (object) ['settings' => ['filter_url_length' => 255]];
      $html = _filter_url($html, $filter);
    }

    // Tidy the HTML markup.
    // @see https://api.html-tidy.org/tidy/quickref_next.html
    if (class_exists('\tidy')) {
      $config = [
        'indent' => FALSE,
        'show-body-only' => TRUE,
        'output-xhtml' => TRUE,
        'wrap' => FALSE,
      ];
      $tidy = new \tidy();
      $tidy->parseString($html, $config, 'utf8');
      $tidy->cleanRepair();
      $html = (string) $tidy;
    }

    return trim($html);
  }

}
