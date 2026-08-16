<?php

namespace Drupal\editoria11y_csa;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;

/**
 * Handles database calls for DashboardController.
 */
class GlobalActions {

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
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Return the dismissals.
   */
  public function globalDismissals(): ?StatementInterface {
    $query = $this->database->select('ed11y_action', 'ed11y_action');
    // 'page_path',
    $query->fields('ed11y_action',
      [
        'result_key',
        'element_id',
        'action_type',
        'created',
      ]
    );
    $query->condition('ed11y_action.action_type', 'okAll');
    $query->groupBy('ed11y_action.element_id');
    $query->groupBy('ed11y_action.result_key');
    $query->groupBy('ed11y_action.created');
    $query->groupBy('ed11y_action.action_type');
    $query->addExpression('MAX(ed11y_action.created)', 'created');
    $query->orderBy('created', 'DESC');
    $query->range(0, 1000);
    return $query->execute();
  }

}
