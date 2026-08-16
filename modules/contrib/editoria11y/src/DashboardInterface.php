<?php

namespace Drupal\editoria11y;

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
  public static function getDismissalOptions(): array;

  /**
   * Gets stale options for select lists.
   *
   * Note:
   * These are used in the "Still on page" filters, so the values are reversed.
   *
   * @return array
   *   Return the stale value options.
   */
  public static function getStaleOptions(): array;

  /**
   * Gets result name (issue types) options for select lists.
   *
   * @return array
   *   Return the result name value options.
   */
  public static function getResultNameOptions(): array;

  /**
   * Gets result name (issue types) options for select lists.
   *
   * @return array
   *   Return the result name value options.
   */
  public static function getDismissalNameOptions(): array;

  /**
   * Gets entity type options for select lists.
   *
   * @return array
   *   Return the entity type value options.
   */
  public static function getEntityTypeOptions(): array;

}
