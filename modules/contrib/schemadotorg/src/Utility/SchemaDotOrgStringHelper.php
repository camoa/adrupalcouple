<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Utility;

/**
 * Helper class Schema.org string methods.
 */
class SchemaDotOrgStringHelper {

  /**
   * Get first sentence from text.
   *
   * @param string $text
   *   The text.
   *
   * @return string
   *   The first sentence from the text.
   */
  public static function getFirstSentence(string $text): string {
    if (!$text || !str_contains($text, '.')) {
      return $text;
    }

    $text = preg_replace_callback(
      '#(etc\.|i\.e\.?|e\.g\.?|\.\.\.| vs\. |(\d*\.)?\d+|https?://[^"]+)#',
      function ($matches) {
        return str_replace('.', '%2E', $matches[0]);
      },
      $text
    );

    if (str_contains($text, '.')) {
      $text = substr($text, 0, strpos($text, '.') + 1);
    }

    $text = str_replace('%2E', '.', $text);

    return $text;
  }

}
