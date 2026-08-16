<?php

namespace Drupal\editoria11y;

use function t;

/**
 * Provides various reusable strings.
 *
 * @phpstan-consistent-constructor
 */
class TestNames {

  /**
   * Returns key/value pairs of known test names.
   */
  public static function coreNames(): array {
    return [
      'ALT_FILE_EXT' => t('This alt text is a filename, not a description'),
      'ALT_MAYBE_BAD' => t('Is this a clear and concise description of the image?'),
      'ALT_MAYBE_BAD_WARNING' => t('Is this a clear and concise description of the image?'),
      'ALT_PLACEHOLDER' => t('This alt text may be a placeholder'),
      'ALT_UNPRONOUNCEABLE' => t('This alt text is unpronounceable'),
      'ARIA_INPUT_FIELD_NAME' => t('This custom input field is missing a label'),
      'BTN_EMPTY' => t('Button is missing an accessible label'),
      'BTN_EMPTY_LABELLEDBY' => t('Button has an invalid ARIA label'),
      'BTN_ROLE_IN_NAME' => t('Button name repeats the word "button"'),
      'BTN_UNPRONOUNCEABLE' => t('This button is unpronounceable'),
      'CONTRAST_ERROR' => t('Text does not have enough contrast to be easily legible'),
      'CONTRAST_ERROR_GRAPHIC' => t('Graphic or icon does not have enough contrast'),
      'CONTRAST_INPUT' => t('Input does not provide enough contrast to be easily legible'),
      'CONTRAST_PLACEHOLDER' => t('Placeholder text does not have enough contrast to be easily legible'),
      'CONTRAST_PLACEHOLDER_UNSUPPORTED' => t('Does this placeholder text have enough contrast?'),
      'CONTRAST_WARNING' => t('Does this text have enough contrast?'),
      'CONTRAST_WARNING_GRAPHIC' => t('Does this graphic or icon have enough contrast?'),
      'DUPLICATE_ID' => t('Duplicate ID attribute'),
      'DUPLICATE_TITLE' => t('This link has a tooltip with the same text as the link'),
      'EMBED_AUDIO' => t('Does this audio have a transcript?'),
      'EMBED_DATA_VIZ' => t('Is this visualization accessible?'),
      'EMBED_GENERAL' => t('Embedded iframes need manual checks'),
      'EMBED_MISSING_TITLE' => t('Frame missing "title" attribute'),
      'EMBED_UNFOCUSABLE' => t('Frame with tabindex="-1" will not be keyboard accessible.'),
      'EMBED_VIDEO' => t('Is this video accurately captioned?'),
      'HEADING_EMPTY' => t('This heading has no text'),
      'HEADING_EMPTY_WITH_IMAGE' => t('This image is used as a heading, so it needs alt text'),
      'HEADING_FIRST' => t('The first heading on this page is a subheading'),
      'HEADING_LONG' => t('Can this heading be shorter?'),
      'HEADING_MISSING_ONE' => t('This page is missing a Heading 1'),
      'HEADING_SKIPPED_LEVEL' => t('This heading is tagged at the wrong level'),
      'HEADING_UNPRONOUNCEABLE' => t('This heading is unpronounceable'),
      'HIDDEN_FOCUSABLE' => t('This element cannot be described by screen readers'),
      'IMAGE_ALT_TOO_LONG' => t('Can this alt text be shorter?'),
      'IMAGE_DECORATIVE' => t('Is this image actually meaningless?'),
      'IMAGE_DECORATIVE_CAROUSEL' => t('Image in a carousel or gallery marked as decorative'),
      'IMAGE_FIGURE_DECORATIVE' => t('This captioned image has no alt text'),
      'IMAGE_FIGURE_DUPLICATE_ALT' => t('Alt text should not be the same as caption text'),
      'LABELS_ARIA_LABEL_INPUT' => t('Is there a visible label for this field?'),
      'LABELS_PLACEHOLDER' => t('Prefer visible labels to placeholders'),
      'LABELS_INPUT_RESET' => t('Is this reset button needed?'),
      'LABELS_MISSING_LABEL' => t('This input has an empty label'),
      'LABEL_IN_NAME' => t('Visible label does not match invisible label'),
      'LABELS_MISSING_IMAGE_INPUT' => t('This image input is missing alt text'),
      'LABELS_NO_FOR_ATTRIBUTE' => t('This input is not connected to a label'),
      'LANG_MISMATCH' => t('Language tag does not match the content'),
      'LANG_OF_PARTS' => t('This content appears to be in a different language'),
      'LANG_OF_PARTS_ALT' => t('This alt text appears to be in a different language'),
      'LINK_ALT_FILE_EXT' => t('Alt text used as a link should not be a URL'),
      'LINK_ALT_MAYBE_BAD' => t('This linked alt might not be clear and concise'),
      'LINK_ALT_MAYBE_BAD_WARNING' => t('This linked alt might not be clear and concise'),
      'LINK_ALT_UNPRONOUNCEABLE' => t('Linked images need pronounceable alt text'),
      'LINK_CLICK_HERE' => t('Manual check: link contains "click here"'),
      'LINK_DOI' => t('Link article titles, not DOI numbers'),
      'LINK_EMPTY' => t('This link contains no words'),
      'LINK_EMPTY_LABELLEDBY' => t('Link with invalid "aria-labelledby" attribute'),
      'LINK_EMPTY_NO_LABEL' => t('This link needs a label'),
      'LINK_FILE_EXT' => t('Link points to a file without warning'),
      'LINK_IDENTICAL_NAME' => t('Links with the same text link to different pages'),
      'LINK_IMAGE_ALT' => t('Does this alt text describe the link or the image?'),
      'LINK_IMAGE_ALT_AND_TEXT' => t('Does this alt text make sense as part of this link?'),
      'LINK_IMAGE_LONG_ALT' => t('Can this linked alt text be shorter?'),
      'LINK_IMAGE_NO_ALT_TEXT' => t('This linked image needs alt text'),
      'LINK_IMAGE_TEXT' => t('Does this linked image need a description?'),
      'LINK_MAYBE_BUTTON' => t('Is this link actually a button?'),
      'LINK_NEW_TAB' => t('Does this link open a new tab without warning?'),
      'LINK_PLACEHOLDER_ALT' => t('This linked alt text may be a placeholder'),
      'LINK_STOPWORD' => t('This link only contains generic words'),
      'LINK_STOPWORD_ARIA' => t('The purpose of this link is visually hidden'),
      'LINK_SUS_ALT' => t("Does this image's alt describe the image or the link?"),
      'LINK_SYMBOLS' => t('Are the symbols or emoji in this link meaningful?'),
      'LINK_UNPRONOUNCEABLE' => t('This link is unpronounceable'),
      'LINK_URL' => t('Link text should not be a URL'),
      'META_LANG' => t('Meta tag for page language missing'),
      'META_LANG_SUGGEST' => t('Did you mean a different language code?'),
      'META_LANG_VALID' => t('Invalid language code'),
      'PAGE_LANG_CONFIDENCE' => t('Page language may not match the content'),
      'META_MAX' => t('Meta tag limits how much users can enlarge text'),
      'META_REFRESH' => t('Meta tag automatically refreshes page'),
      'META_SCALABLE' => t('Meta tag prevents users from enlarging text'),
      'META_TITLE' => t('Meta tag for page title missing'),
      'MISSING_ALT' => t('Invalid HTML: image has no alt attribute'),
      'MISSING_ALT_LINK' => t('Invalid HTML: linked image missing alt attribute'),
      'MISSING_ALT_LINK_HAS_TEXT' => t('Invalid HTML: image in link missing alt attribute'),
      'QA_BAD_LINK' => t('This link target may be invalid'),
      'QA_BLOCKQUOTE' => t('Should this quote be a heading?'),
      'QA_DOCUMENT' => t('Has this document been tagged for screen readers?'),
      'QA_FAKE_HEADING' => t('Should this bold text be a heading?'),
      'QA_FAKE_LIST' => t('Should this have list formatting?'),
      'QA_IN_PAGE_LINK' => t('Broken same-page link'),
      'QA_JUSTIFY' => t('Do not justify text'),
      'QA_NESTED_COMPONENTS' => t('Nested interactive layout components'),
      'QA_PDF' => t('Is there an alternative for this PDF?'),
      'QA_SMALL_TEXT' => t('Text is too small'),
      'QA_STRONG_ITALICS' => t('Large blocks of emphasized text are harder to read'),
      'QA_SUBSCRIPT' => t('Do not use sub/superscript as visual formatting'),
      'QA_UNDERLINE' => t('Only links should be underlined'),
      'QA_UPPERCASE' => t('Is this uppercase text needed?'),
      'SUS_ALT' => t('Are there redundant words in this alt text?'),
      'TABINDEX_ATTR' => t('Tabindex overrides interrupt the focus order'),
      'TABLES_EMPTY_HEADING' => t('This header cell needs text'),
      'TABLES_INVALID_HEADERS_REF' => t('This table has an invalid headers attribute'),
      'TABLES_MISSING_HEADINGS' => t('This table needs a header row and/or column'),
      'TABLES_SEMANTIC_HEADING' => t('Content headings should not be used inside tables'),
      'UNCONTAINED_LI' => t('Invalid HTML list'),
    ];
  }

  /**
   * Returns list of tests defined as content.
   */
  public static function contentTests(): array {
    return [
      'ALT_FILE_EXT',
      'ALT_MAYBE_BAD',
      'ALT_MAYBE_BAD_WARNING',
      'ALT_PLACEHOLDER',
      'ALT_UNPRONOUNCEABLE',
      'EMBED_AUDIO',
      'EMBED_CUSTOM',
      'EMBED_DATA_VIZ',
      'EMBED_GENERAL',
      'EMBED_MISSING_TITLE',
      'EMBED_VIDEO',
      'HEADING_EMPTY',
      'HEADING_EMPTY_WITH_IMAGE',
      'HEADING_LONG',
      'HEADING_SKIPPED_LEVEL',
      'HEADING_UNPRONOUNCEABLE',
      'IMAGE_ALT_TOO_LONG',
      'IMAGE_DECORATIVE',
      'IMAGE_FIGURE_DECORATIVE',
      'IMAGE_FIGURE_DUPLICATE_ALT',
      'LINK_ALT_FILE_EXT',
      'LINK_ALT_MAYBE_BAD',
      'LINK_ALT_MAYBE_BAD_WARNING',
      'LINK_ALT_UNPRONOUNCEABLE',
      'LINK_DOI',
      'LINK_EMPTY',
      'LINK_EMPTY_NO_LABEL',
      'LINK_FILE_EXT',
      'LINK_IMAGE_ALT',
      'LINK_IMAGE_ALT_AND_TEXT',
      'LINK_IMAGE_LONG_ALT',
      'LINK_IMAGE_NO_ALT_TEXT',
    // @todo turn off?
      'LINK_IMAGE_TEXT',
      'LINK_NEW_TAB',
      'LINK_PLACEHOLDER_ALT',
      'LINK_STOPWORD',
      'LINK_SUS_ALT',
      'LINK_SYMBOLS',
      'LINK_URL',
      'LINK_UNPRONOUNCEABLE',
      'MISSING_ALT',
      'MISSING_ALT_LINK',
      'MISSING_ALT_LINK_HAS_TEXT',
      'QA_BAD_LINK',
      'QA_BLOCKQUOTE',
      'QA_DOCUMENT',
      'QA_FAKE_HEADING',
      'QA_FAKE_LIST',
      'QA_IN_PAGE_LINK',
      'QA_JUSTIFY',
      'QA_PDF',
      'QA_STRONG_ITALICS',
      'QA_SUBSCRIPT',
      'QA_UNDERLINE',
      'QA_UPPERCASE',
      'SUS_ALT',
      'TABLES_EMPTY_HEADING',
      'TABLES_MISSING_HEADINGS',
      'TABLES_SEMANTIC_HEADING',
    ];
  }

  /**
   * Gets names of content tests.
   */
  public static function contentTestNames(): array {
    $names = array_intersect_key(self::coreNames(), array_flip(self::contentTests()));
    return array_combine(
      array_keys($names),
      array_map(fn($key, $label) => $key . ': ' . $label, array_keys($names), $names)
    );
  }

  /**
   * Gets names of dev tests.
   */
  public static function devTestNames(): array {
    return array_diff(self::coreNames(), self::contentTestNames());
  }

  /**
   * Gets tests that should be off by default on config page.
   */
  public static function offByDefault(): array {
    return [
      'CONTRAST_WARNING_GRAPHIC',
      'LINK_CLICK_HERE',
      'LINK_IMAGE_ALT',
    ];
  }

  /**
   * Returns the set of template (non-content) tests defined in coreNames().
   *
   * Template tests describe markup the editor cannot fix without developer
   * intervention (contrast, ARIA wiring, page-level meta, form structure, …)
   * and are only meaningful when the CSA developer profile is active. In
   * CSA-inactive installs the controller appends these keys to
   * `ignore_tests` so the library does not run them in the browser.
   */
  public static function templateTests(): array {
    return array_values(array_diff(
      array_keys(self::coreNames()),
      self::contentTests(),
    ));
  }

  /**
   * Returns keys that ship enabled in the upstream library but are inactive.
   *
   * Two categories are bundled here:
   *
   * - LINK_LABEL: an upstream artifact, not a real test. It appears in the
   *   library defaults but no rule implementation runs, so the entry only
   *   pollutes `Drupal.Ed11y.State.option.checks`. It is intentionally
   *   absent from coreNames().
   * - LANG_MISMATCH, LANG_OF_PARTS, LANG_OF_PARTS_ALT, PAGE_LANG_CONFIDENCE:
   *   real upstream checks, but they require `langOfPartsPlugin` to be
   *   enabled. The Drupal module does not enable that plugin yet, so the
   *   keys surface as active in State.option but are never actually called.
   *   They remain in coreNames() because the catalogue should describe the
   *   long-term intent, but they are added to the always-ignore list until
   *   the plugin is wired up.
   *
   * The controller always appends this list to `ignore_tests`, regardless
   * of CSA state.
   */
  public static function libraryArtifacts(): array {
    return [
      'LINK_LABEL',
      'LANG_MISMATCH',
      'LANG_OF_PARTS',
      'LANG_OF_PARTS_ALT',
      'PAGE_LANG_CONFIDENCE',
    ];
  }

  /**
   * Returns the group identifier for a test key.
   *
   * The order of checks matters: the first match wins, which is why e.g. QA
   * is tested before LINK (so that QA_BAD_LINK lands in legibility, not
   * links), and alts is tested before links (so MISSING_ALT_LINK lands in
   * alts, not links).
   *
   * @param string $key
   *   The test key (e.g. "HEADING_EMPTY").
   *
   * @return string
   *   A test group identifier (e.g. "headings", "alts").
   */
  public static function groupForKey(string $key): string {
    if (str_contains($key, 'LINK_ALT') ||
        str_contains($key, 'LINK_IMAGE') ||
        str_contains($key, 'ALT_LINK')) {
      return 'link_alt';
    }
    if (
    // Forms.
      $key === 'TABINDEX_ATTR' ||
      str_starts_with($key, 'FOCUSABLE') ||
    // Forms.
      str_contains($key, 'LABELLEDBY') ||
    // Forms.
      str_contains($key, 'FOCUS') ||
    // Move to layout.
      $key === 'QA_NESTED_COMPONENTS'
    ) {
      return 'aria';
    }
    if (
      str_starts_with($key, 'META')

    ) {
      return 'meta';
    }
    if (str_starts_with($key, 'HEADING') || $key === 'QA_BLOCKQUOTE' || $key === 'QA_FAKE_HEADING') {
      return 'headings';
    }
    if (str_starts_with($key, 'CONTRAST')) {
      return 'contrast';
    }
    if (str_starts_with($key, 'TABLES')) {
      return 'tables';
    }
    if (
      str_contains($key, 'ALT_') ||
      str_contains($key, '_ALT') ||
      str_starts_with($key, 'IMAGE_') ||
      str_starts_with($key, 'MISSING_ALT') ||
      $key === 'SUS_ALT'
    ) {
      return 'alts';
    }

    if (str_starts_with($key, 'EMBED')) {
      return 'embeds';
    }
    if (str_starts_with($key, 'LABEL') || str_starts_with($key, 'BTN')) {
      return 'forms';
    }
    if (str_contains($key, 'LINK') ||
        $key === 'DUPLICATE_ID' ||
        str_contains($key, 'PDF') ||
        str_contains($key, 'DOI') ||
        $key === 'DUPLICATE_TITLE') {
      return 'links_content';
    }

    return 'legibility';
  }

  /**
   * Returns group identifiers and translated labels in display order.
   *
   * @return array
   *   Ordered array keyed by group id, values are translated labels.
   */
  public static function groupLabels(): array {
    return [
      'links_content' => t('Links'),
      'link_alt' => t('Linked images'),
      'alts' => t('Images'),
      'headings' => t('Headings'),
      'tables' => t('Tables'),
      'legibility' => t('Text formatting'),
      'embeds' => t('Video, audio, frames'),
      'contrast' => t('Contrast'),
      'forms' => t('Forms'),
      'aria' => t('ARIA and focus'),
      'meta' => t('Page metadata'),
    ];
  }

  /**
   * Returns the set identifier for a given group.
   *
   * @param string $group
   *   The group identifier.
   *
   * @return string
   *   The set identifier.
   */
  public static function groupSet($group): string {
    $sets = [
      'headings' => 'content_tests',
      'alts' => 'content_tests',
      'link_alt' => 'content_tests',
      'tables' => 'content_tests',
      'legibility' => 'content_tests',
      'embeds' => 'content_tests',
      'links_content' => 'content_tests',
      'contrast' => 'template_tests',
      'forms' => 'template_tests',
      'aria' => 'template_tests',
      'meta' => 'template_tests',
    ];
    return $sets[$group] ?? 'content_tests';
  }

  /**
   * Filters to tests with results in the DB, and adds custom tests.
   *
   * @param string $type
   *   Request 'results' or 'dismissals'.
   */
  public function activeNames(string $type = 'results'): array {
    $staticTests = $this->coreNames();
    $names = [];

    // @phpstan-ignore-next-line
    $database = \Drupal::database();

    // $table = $type === 'results' ? 'ed11y_result' : 'ed11y_action';
    if ($type == 'results') {
      $results = $database->select('ed11y_result', 't')
        ->fields('t', ['result_key', 'result_name'])
        ->groupBy('result_key')
        ->groupBy('result_name')
        ->orderBy('result_name')
        ->execute();
    }
    else {
      $results = $database->select('ed11y_action', 't')
        ->fields('t', ['result_key', 'result_name'])
        ->groupBy('result_key')
        ->groupBy('result_name')
        ->orderBy('result_name')
        ->execute();
    }

    foreach ($results as $result) {
      if (!in_array($result->result_key, $names)) {
        if (in_array($result->result_key, $staticTests)) {
          // Translatable value from known test.
          $names[$result->result_key] = $staticTests[$result->result_key];
        }
        else {
          // Raw value from custom test.
          $names[$result->result_key] = $result->result_name;
        }
      }
    }
    return $names;
  }

}
