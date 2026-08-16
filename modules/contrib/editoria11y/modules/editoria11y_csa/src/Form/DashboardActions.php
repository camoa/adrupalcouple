<?php

declare(strict_types=1);

namespace Drupal\editoria11y_csa\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Provides a Editoria11y form.
 */
final class DashboardActions extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'editoria11y_csa_dashboard_actions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $exporter = Views::getView('editoria11y_export');
    if ($exporter) {
      $exporter->setDisplay('pages');
      $pageUrl = $exporter->getUrl();
      $exporter->setDisplay('dismissals');
      $dismissUrl = $exporter->getUrl();
      $exporter->setDisplay('results');
      $alertUrl = $exporter->getUrl();

      $form['export'] = [
        '#type' => 'fieldset',
        '#title' => t('Export'),
        [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#items' => [
            [
              '#type' => 'link',
              '#title' => t('Export pages'),
              '#url' => $pageUrl,
            ],
            [
              '#type' => 'link',
              '#title' => t('Export dismissals'),
              '#url' => $dismissUrl,
            ],
            [
              '#type' => 'link',
              '#title' => t('Export alerts'),
              '#url' => $alertUrl,
            ],
          ],
        ],
      ];

    }

    $pageView = Link::createFromRoute(
      $this->t('Paths with parameters'),
      'view.ed11y_result.pages_with_alerts',
      ['page_path' => '?']
    )->toString();

    $form['maintenance'] = [
      '#type' => 'fieldset',
      '#title' => 'Routine refresh',
    ];

    $form['maintenance']['check_deleted'] = [
      '#title' => $this->t("Drop records for deleted or moved pages"),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
      '#description' => $this->t('Deletes results that no longer have valid paths or entity references.'),
    ];

    // @todo remove stale dismissals.
    /*
    $form['maintenance']['remove_stale'] = [
    '#title' => $this->t("Check for stale dismissals"),
    '#type' => 'checkbox',
    '#default_value' => TRUE,
    '#description' => $this->t('Discards dismissals for alerts where the
    element disappeared or was fixed more than a year ago.'),
    ];
     */

    $form['maintenance']['update_titles'] = [
      '#title' => $this->t("Update page titles"),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#description' => $this->t('Replaces recorded titles for Nodes, Terms and Users with the current value on their edit page.'),
    ];

    $form['maintenance']['update_paths'] = [
      '#title' => $this->t("Update page paths"),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#description' => $this->t('Replaces recorded URLs for Nodes, Terms and Users with their current canonical path. <br>@ReviewPaths (node/1?node=2) will not be updated.', ['@ReviewPaths' => $pageView]),
    ];

    $form['maintenance']['danger'] = [
      '#title' => $this->t('Risky deletions'),
      '#type' => 'details',
    ];

    $form['maintenance']['danger']['remove_pages_with_params'] = [
      '#title' => $this->t("Delete records for paths with parameters (e.g., ?page=2)"),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'entities' => $this->t('Only for nodes, terms & users'),
        'all' => $this->t('All, including search and views'),
      ],
      '#default_value' => 'none',
      '#description' => $this->t('Used to remove duplicate records when previously recorded parameters like page numbers or search keys were not meaningful. Parameters that are not meaningful should be removed from the list of parameters to save under "Syncing results to reports" in the module configuration.<br>@ReviewPaths should be reviewed before proceeding. <strong>There is no undo</strong>.', ['@ReviewPaths' => $pageView]),
    ];

    $form['maintenance']['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Start'),
      ],
    ];

    $crawler = Views::getView('ed11y_crawler');
    if ($crawler) {
      $crawler->setDisplay('content');
      $url = $crawler->getUrl();
      $form['Manage results'] = [
        '#type' => 'fieldset',
        '#title' => t('Recheck nodes'),
        [
          '#type' => 'link',
          // @todo query param to not load images and drop stale results.
          '#title' => t('Set up a crawl'),
          '#url' => $url,
          '#attributes' => [
            'class' => ['button'],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * Compares Editoria11y DB tables to Drupal tables and routes.
   *
   * @param int $batch_id
   *   Incremented integer.
   * @param int $batch_size
   *   Records to process.
   * @param int $max
   *   Total number of batches.
   * @param array $form_values
   *   User choices for the batch process.
   * @param array $context
   *   The Drupal batch environment variables.
   */
  public static function batchProcess(int $batch_id, int $batch_size, int $max, array $form_values, array &$context): void {
    if (!isset($context['results']['progress'])) {
      // First round; initialize variables.
      $context['results']['last_record'] = 0;
      $context['results']['max'] = $max;
      $context['results']['updated'] = 0;
      $context['results']['deleted'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['process'] = 'Form batch completed';
    }

    // Normalize the option flags once. Checkbox values arrive as int 1/0, but
    // test loosely so the batch still behaves if a value reaches the operation
    // as a string when re-dispatched.
    $check_deleted = !empty($form_values['check_deleted']);
    $update_titles = !empty($form_values['update_titles']);
    $update_paths = !empty($form_values['update_paths']);
    $remove_params = $form_values['remove_pages_with_params'] ?? 'none';

    // Message above progress bar (1-based batch number out of the total count).
    $context['message'] = t('Processing batch #@batch_id of @count.', [
      '@batch_id' => number_format($batch_id + 1),
      '@count' => number_format($context['results']['max']),
    ]);

    // \Drupal::database() is intentional: this is a static batch callback
    // and cannot use constructor injection.
    $database = \Drupal::database();
    $query = $database->select('ed11y_page');
    $query->fields(
      'ed11y_page',
      [
        'pid',
        'page_path',
        'page_language',
        'entity_id',
        'entity_type',
        'page_title',
        'route_name',
      ],
    );
    $query->leftJoin(
      'node_field_data',
      'node_field_data',
      "ed11y_page.route_name = 'entity.node.canonical' AND node_field_data.nid = ed11y_page.entity_id AND (node_field_data.langcode = ed11y_page.page_language OR node_field_data.langcode IN ('und', 'zxx'))"
    );
    $query->fields('node_field_data',
      [
        'nid',
        'title',
      ],
    );
    $query->leftJoin(
      'users_field_data',
      'users_field_data',
      "ed11y_page.route_name = 'entity.user.canonical' AND users_field_data.uid = ed11y_page.entity_id AND (users_field_data.langcode = ed11y_page.page_language OR users_field_data.langcode IN ('und', 'zxx'))"
    );
    $query->fields('users_field_data',
      [
        'uid',
        'name',
      ],
    );
    $query->leftJoin(
      'taxonomy_term_field_data',
      'taxonomy_term_field_data',
      "ed11y_page.route_name = 'entity.taxonomy_term.canonical' AND taxonomy_term_field_data.tid = ed11y_page.entity_id AND (taxonomy_term_field_data.langcode = ed11y_page.page_language OR taxonomy_term_field_data.langcode IN ('und', 'zxx'))"
    );
    $query->fields('taxonomy_term_field_data',
      [
        'tid',
        'name',
      ],
    );
    $query->orderBy('pid');
    $query->condition('pid', $context['results']['last_record'], '>');
    $query->range(0, $batch_size);
    $results = $query->execute()->fetchAll();

    $pages_to_delete = [];
    $paths_to_update = [];
    $titles_to_update = [];
    $count = count($results);

    $path_validator = \Drupal::service('path.validator');
    // Hoisted out of the row loop: resolving a service from the container on
    // every record is needless overhead.
    $alias_manager = \Drupal::service('path_alias.manager');

    foreach ($results as $record) {
      $path_params = FALSE;
      if (str_contains($record->page_path, '?')) {
        $path_params = explode("?", $record->page_path, 2)[1];
      }

      switch ($record->route_name) {
        case 'entity.node.canonical':
          if (empty($record->nid) && $check_deleted
          ) {
            $pages_to_delete[] = $record->pid;
            break;
          }
          elseif (
            $path_params &&
            $remove_params !== 'none'
            ) {
            $pages_to_delete[] = $record->pid;
            break;
          }

          if ($update_titles &&
            $record->page_title !== $record->title
          ) {
            $titles_to_update[$record->pid] = $record->title;
          }

          if ($update_paths) {
            $internal_path = '/node/' . $record->entity_id;
            $alias = $alias_manager->getAliasByPath($internal_path, $record->page_language);
            if ($path_params) {
              $alias = $alias . '?' . $path_params;
            }
            if (
              $alias !== $record->page_path &&
              $alias !== str_replace('/' . $record->page_language, '', $record->page_path)
            ) {
              $paths_to_update[$record->pid] = $alias;
            }
          }

          break;

        case 'entity.taxonomy_term.canonical':
          if (empty($record->tid) && $check_deleted
          ) {
            $pages_to_delete[] = $record->pid;
            break;
          }
          elseif (
            $path_params &&
            $remove_params !== 'none'
          ) {
            $pages_to_delete[] = $record->pid;
            break;
          }

          if ($update_titles &&
            $record->page_title !== $record->taxonomy_term_field_data_name
          ) {
            $titles_to_update[$record->pid] = $record->taxonomy_term_field_data_name;
          }

          if ($update_paths) {
            // Canonical system path for a term is /taxonomy/term/{tid}.
            $internal_path = '/taxonomy/term/' . $record->entity_id;
            $alias = $alias_manager->getAliasByPath($internal_path, $record->page_language);
            if ($path_params) {
              $alias = $alias . '?' . $path_params;
            }
            if (
              $alias !== $record->page_path &&
              $alias !== str_replace('/' . $record->page_language, '', $record->page_path)
            ) {
              $paths_to_update[$record->pid] = $alias;
            }
          }

          break;

        case 'entity.user.canonical':
          if (empty($record->uid) && $check_deleted
          ) {
            $pages_to_delete[] = $record->pid;
            break;
          }
          elseif (
            $path_params &&
            $remove_params !== 'none'
          ) {
            $pages_to_delete[] = $record->pid;
            break;
          }

          if ($update_titles &&
            $record->page_title !== $record->name
          ) {
            $titles_to_update[$record->pid] = $record->name;
          }

          if ($update_paths) {
            // Canonical system path for a user is /user/{uid}.
            $internal_path = '/user/' . $record->entity_id;
            $alias = $alias_manager->getAliasByPath($internal_path, $record->page_language);
            if ($path_params) {
              $alias = $alias . '?' . $path_params;
            }
            if (
              $alias !== $record->page_path &&
              $alias !== str_replace('/' . $record->page_language, '', $record->page_path)
            ) {
              $paths_to_update[$record->pid] = $alias;
            }
          }

          break;

        default:
          // Views and other entity types.
          // Delete if it has parameters we want to drop.
          if ($remove_params === 'all' &&
              $path_params
            ) {
            $pages_to_delete[] = $record->pid;
            break;
          }

          // Without the access check: this maintenance pass must not delete a
          // valid record just because the operator lacks access to its path.
          $url_object = $path_validator->getUrlIfValidWithoutAccessCheck($record->page_path);
          // Delete if the path is gone.
          if (!$url_object instanceof Url) {
            if ($check_deleted) {
              $pages_to_delete[] = $record->pid;
            }
            break;
          }
          if (!$path_params && $update_paths) {
            $alias = $url_object->toString();
            if (
              $alias !== $record->page_path &&
              $alias !== str_replace('/' . $record->page_language, '', $record->page_path)
            ) {
              $paths_to_update[$record->pid] = $alias;
            }
          }

          break;

      }

      // Rows arrive in ascending pid order, so the keyset cursor is simply the
      // last pid seen this round.
      $context['results']['last_record'] = $record->pid;
    }

    // One transaction per batch keeps the deletes (across three tables) and the
    // updates atomic, so a mid-batch failure can't orphan results/actions or
    // leave a row half-updated. Committed when $transaction is released below.
    $transaction = $database->startTransaction();

    if (count($pages_to_delete) > 0) {
      $database->delete('ed11y_result')->condition('pid', $pages_to_delete, 'IN')->execute();
      $database->delete('ed11y_action')->condition('pid', $pages_to_delete, 'IN')->execute();
      $database->delete('ed11y_page')->condition('pid', $pages_to_delete, 'IN')->execute();
      // Count rows removed, not batches that removed something.
      $context['results']['deleted'] += count($pages_to_delete);
    }

    // Merge path + title changes so a row needing both is written in a single
    // UPDATE instead of two. $paths_to_update / $titles_to_update are only
    // populated when their respective option is enabled.
    $fields_by_pid = [];
    foreach ($paths_to_update as $pid => $path) {
      $fields_by_pid[$pid]['page_path'] = $path;
    }
    foreach ($titles_to_update as $pid => $title) {
      $fields_by_pid[$pid]['page_title'] = $title;
    }
    foreach ($fields_by_pid as $pid => $fields) {
      $database->update('ed11y_page')
        ->condition('pid', $pid)
        ->fields($fields)
        ->execute();
      $context['results']['updated']++;
    }

    // Release the batch transaction (commits on destruct) before reporting.
    unset($transaction);

    // Keep track of progress (actual rows read this round, which may be fewer
    // than $batch_size on the final round or after deletions).
    $context['results']['progress'] += $count;
  }

  /**
   * Sends messages to UI and logs on complete.
   *
   * @param bool $success
   *   Did it work?
   * @param array $results
   *   Values forwarded from the batch process.
   * @param array $operations
   *   Where we were when things blew up.
   * @param string $elapsed
   *   How long the batch took.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    // Grab the messenger service, this will be needed if the batch was a
    // success or a failure.
    $messenger = \Drupal::messenger();
    if ($success) {
      // Merge defaults so a batch that ran zero operations (or failed before
      // the first one populated $context['results']) still renders cleanly.
      $results += [
        'process' => 'Form batch completed',
        'progress' => 0,
        'deleted' => 0,
        'updated' => 0,
      ];

      // The success variable was true, which indicates that the batch process
      // was successful (i.e. no errors occurred).
      // Show success message to the user.
      $messenger->addMessage(t('@process processed @count, deleted @deleted, updated @updated.', [
        '@process' => $results['process'],
        '@count' => $results['progress'],
        '@deleted' => $results['deleted'],
        '@updated' => $results['updated'],
        '@elapsed' => $elapsed,
      ]));
      // Log the batch success.
      \Drupal::logger('Editoria11y database maintenance')->info(
        '@process processed @count, deleted @deleted, updated @updated in @elapsed.', [
          '@process' => $results['process'],
          '@count' => $results['progress'],
          '@deleted' => $results['deleted'],
          '@updated' => $results['updated'],
          '@elapsed' => $elapsed,
        ]);
    }
    else {
      // An error occurred. $operations contains the operations that remained
      // unprocessed. Pick the last operation and report on what happened.
      $error_operation = reset($operations);
      if ($error_operation) {
        $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
          '%error_operation' => print_r($error_operation[0], TRUE),
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
        $messenger->addError($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $batch = new BatchBuilder();
    $batch->setTitle('Running batch process.')
      ->setFinishCallback([self::class, 'batchFinished'])
      ->setInitMessage('Commencing')
      ->setProgressMessage('Processing...')
      ->setErrorMessage('An error occurred during processing.');

    // Create 10 chunks of 100 items.
    // @phpstan-ignore-next-line
    $database = \Drupal::database();
    $batch_size = 100;
    $results_query = $database->select('ed11y_page');
    $result_count = (int) $results_query->countQuery()->execute()->fetchField();

    if ($result_count === 0) {
      $this->messenger()->addStatus($this->t('No records to process.'));
      $form_state->setRedirectUrl(new Url('editoria11y_csa.dashboard_actions'));
      return;
    }

    $batches = (int) ceil($result_count / $batch_size);

    for ($i = 0; $i < $batches; $i++) {
      $args = [
        $i,
        $batch_size,
        $batches,
        $form_state->getValues(),
      ];
      $batch->addOperation([self::class, 'batchProcess'], $args);
    }
    batch_set($batch->toArray());

    // Set the redirect for the form submission back to the form itself.
    $form_state->setRedirectUrl(new Url('editoria11y_csa.dashboard_actions'));
  }

}
