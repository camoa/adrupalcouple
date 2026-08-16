<?php

declare(strict_types=1);

namespace Drupal\markdown_easy;

/**
 * Utility methods for the Markdown Easy module.
 */
final class MarkdownUtility {

  /**
   * Returns the filter weights of the filters we care about.
   *
   * @param array<mixed> $filters
   *   Array of all filters for a given text format.
   *
   * @return array<string, int|NULL>
   *   Array of weights for the filters we care about.
   */
  public function getFilterWeights(array $filters): array {
    $weight = [];
    $weight['filter_html'] = NULL;
    $weight['markdown_easy'] = NULL;
    foreach ($filters as $filter_key => $filter) {
      if ((bool) $filter['status']) {
        $filters[$filter_key] = $filter;
        // Limit HTML filter.
        if ($filter_key == 'filter_html') {
          $weight['filter_html'] = $filter['weight'];
        }
        // Markdown Easy filter.
        if ($filter_key == 'markdown_easy') {
          $weight['markdown_easy'] = $filter['weight'];
        }
      }
    }
    return $weight;
  }

  /**
   * Parses a space-separate list of HTML tags and attributes.
   *
   * @param string $input
   *   Space-separated list of HTML tags and attributes.
   *
   * @return array<string, array<null|string>>
   *   HTML tags and attributes in array form.
   */
  protected function parseTagList(string $input): array {
    $result = [];

    preg_match_all('/<([a-z0-9]+)([^>]*)>/i', $input, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $tag = strtolower($match[1]);
      $attrs = [];

      if (!empty(trim($match[2]))) {
        preg_match_all('/([a-zA-Z0-9_-]+)(?:\s*=\s*\'[^\']*\')?/', $match[2], $attr_matches);
        $attrs = array_map('strtolower', $attr_matches[1]);
      }

      if (!isset($result[$tag])) {
        $result[$tag] = $attrs;
      }
      else {
        $result[$tag] = array_unique(array_merge($result[$tag], $attrs));
      }

      sort($result[$tag]);
    }

    return $result;
  }

  /**
   * Compares two lists of HTML tags and attributes.
   *
   * @param string $flavor
   *   Name of the Markdown Easy flavor being used.
   * @param string $allowed_html
   *   Space-separated list of HTML tags and attributes allowed.
   *
   * @return array<string, array<null|string>>
   *   Array of missing tags and attributes.
   */
  public function findMissingTags(string $flavor, string $allowed_html): array {
    $allowed = $this->parseTagList($allowed_html);

    $required_sets = [
      // Tags and attributes output by CommonMarkCoreExtension.
      'standard' => [
        'p' => [],
        'em' => [],
        'strong' => [],
        'a' => ['href', 'title'],
        'img' => ['alt', 'src', 'title'],
        'code' => [],
        'pre' => [],
        'blockquote' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'hr' => [],
        'br' => [],
      ],
      // Tags and attributes output by GithubFlavoredMarkdownExtension.
      'github' => [
        'del' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['class'],
        'td' => ['class'],
        'input' => ['checked', 'disabled', 'type'],
      ],
      // Tags and attributes output by FootnoteExtension and
      // DescriptionListExtension.
      'markdownsmorgasbord' => [
        'sup' => ['id'],
        'dl' => [],
        'dt' => [],
        'dd' => [],
        'li' => ['class', 'role', 'id'],
        'a' => ['href', 'class', 'role', 'title'],
      ],
    ];

    if (!isset($required_sets[$flavor])) {
      throw new \InvalidArgumentException("Unknown flavor: $flavor");
    }

    $sets_to_check = ['standard'];
    if ($flavor === 'github') {
      $sets_to_check = ['standard', 'github'];
    }
    elseif ($flavor === 'markdownsmorgasbord') {
      $sets_to_check = ['standard', 'github', 'markdownsmorgasbord'];
    }
    // Merge and normalize required tags/attributes.
    $required = [];
    foreach ($sets_to_check as $set_name) {
      foreach ($required_sets[$set_name] as $tag => $attrs) {
        if (!isset($required[$tag])) {
          $required[$tag] = $attrs;
        }
        else {
          $required[$tag] = array_unique(array_merge($required[$tag], $attrs));
        }
        sort($required[$tag]);
      }
    }

    // Compared allowed with required.
    $missing = array_filter($required, function ($required_attrs, $tag) use ($allowed) {
      return !isset($allowed[$tag]) || array_diff($required_attrs, $allowed[$tag]);
    }, ARRAY_FILTER_USE_BOTH);

    return $missing;
  }

  /**
   * Outputs an array of tags and attributes as a human-readable string.
   *
   * @param array<string, array<null|string>> $tagArray
   *   The array of tags and attributes.
   *
   * @return string
   *   Human-readable string like "<a href title> <h1>".
   */
  public function formatHtmlTags(array $tagArray): string {
    $result = [];

    foreach ($tagArray as $tag => $attributes) {
      if (!empty($attributes)) {
        $attr_string = implode(' ', $attributes);
        $result[] = "<{$tag} {$attr_string}>";
      }
      else {
        $result[] = "<{$tag}>";
      }
    }

    return implode(' ', $result);
  }

}
