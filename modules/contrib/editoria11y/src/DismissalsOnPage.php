<?php

namespace Drupal\editoria11y;

use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageInterface;

/**
 * Handles database calls for DashboardController.
 */
class DismissalsOnPage {

  /**
   * Database property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a new connection object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database property.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * Function to get the dismissals.
   *
   * Stored page_language is the referenced entity's own langcode, which can
   * be 'und' or 'zxx' for language-neutral content. An exact match on the
   * resolved page language is preferred; when it finds nothing, fall back to
   * the language-neutral codes (mirroring the dashboard Views joins) so
   * dismissals recorded against neutral rows still reach the page.
   *
   * @param string $page_path
   *   Page path property.
   * @param string $page_language
   *   The resolved language of the page content.
   *
   * @return array
   *   The dismissal records for the page.
   */
  public function getDismissals(string $page_path, string $page_language): array {
    $rows = $this->query($page_path, [$page_language]);
    if ($rows) {
      return $rows;
    }
    $neutral = array_diff(
      [
        LanguageInterface::LANGCODE_NOT_SPECIFIED,
        LanguageInterface::LANGCODE_NOT_APPLICABLE,
      ],
      [$page_language]
    );
    if ($neutral) {
      return $this->query($page_path, $neutral);
    }
    return [];
  }

  /**
   * Fetches page + dismissal rows for a path in any of the given languages.
   *
   * @param string $page_path
   *   Page path property.
   * @param string[] $page_languages
   *   Language codes to match against ed11y_page.page_language.
   *
   * @return array
   *   The matching rows.
   */
  protected function query(string $page_path, array $page_languages): array {
    $query = $this->database->select('ed11y_page', 'ed11y_page');
    $query->leftJoin('ed11y_action', 'ed11y_action', 'ed11y_action.pid = ed11y_page.pid');
    $query->fields('ed11y_action',
      ['uid',
        'result_key',
        'element_id',
        'action_type',
      ]
    );
    $query->fields('ed11y_page',
      ['pid']
    );
    $query->condition('ed11y_page.page_path', $page_path);
    $query->condition('ed11y_page.page_language', $page_languages, 'IN');
    return $query->execute()->fetchAll();
  }

}
