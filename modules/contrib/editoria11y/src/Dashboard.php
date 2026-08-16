<?php

namespace Drupal\editoria11y;

use Drupal\Core\Database\Connection;

/**
 * Handles database calls for DashboardController.
 */
class Dashboard implements DashboardInterface {
  /**
   * Database property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructs a dashboard object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database property.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritDoc}
   */
  public static function getDismissalOptions(): array {
    return [
      'hide' => t("hide"),
      'ok' => t('ok'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getStaleOptions(): array {
    return [
      '0' => t("Yes"),
      '1' => t("No"),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getDismissalNameOptions(): array {

    return (new TestNames())->activeNames('dismissals');
  }

  /**
   * {@inheritDoc}
   */
  public static function getResultNameOptions(): array {

    return (new TestNames())->activeNames();
  }

  /**
   * {@inheritDoc}
   */
  public static function getEntityTypeOptions(): array {

    $database = \Drupal::database();

    $entity_types = $database->select('ed11y_page', 't')
      ->fields('t', ['entity_type'])
      ->groupBy('entity_type')
      ->orderBy('entity_type')
      ->execute()
      ->fetchCol();

    return array_combine($entity_types, $entity_types);
  }

}
