<?php

namespace Drupal\editoria11y;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Handles database calls for DashboardController.
 */
interface DashboardInterface {

  /**
   * Gets dismissal options for select lists.
   *
   * @return array
   *   Return the dismissal value options.
   */
  static public function getDismissalOptions(): array;

  /**
   * Gets stale options for select lists.
   *
   * Note:
   * These are used in the "Still on page" filters, so the values are reversed.
   *
   * @return array
   *   Return the stale value options.
   */
  static public function getStaleOptions(): array;

  /**
   * Gets result name (issue types) options for select lists.
   *
   * @return array
   *   Return the result name value options.
   */
  static public function getResultNameOptions(): array;

  /**
   * Gets entity type options for select lists.
   *
   * @return array
   *   Return the entity type value options.
   */
  static public function getEntityTypeOptions(): array;

  /**
   * ExportPages function.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows.
   */
  public function exportPages(): StatementInterface;

  /**
   * Export dismissals function.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows.
   */
  public function exportDismissals(): StatementInterface;

  /**
   * Function to export the issues.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows
   */
  public function exportIssues(): StatementInterface;

}
