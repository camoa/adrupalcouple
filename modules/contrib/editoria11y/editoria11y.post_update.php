<?php

/**
 * @file
 * Post update functions for Editoria11y.
 */

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\editoria11y\Api;

/**
 * Check for missing dashboard Views.
 */
function editoria11y_post_update_0001(): void {
  /*
   * Moved to post_update so that `drush config:import` can
   * supply these Views with UUIDs before this hook runs.
   */
  $views_storage = \Drupal::entityTypeManager()->getStorage('view');
  assert($views_storage instanceof ConfigEntityStorageInterface);

  $sync_storage = \Drupal::service('config.storage.sync');
  $optional_storage = new FileStorage(
    \Drupal::moduleHandler()->getModule('editoria11y')->getPath() . '/config/optional'
  );

  $created = FALSE;
  foreach (['ed11y_action', 'ed11y_result'] as $view_id) {
    if ($views_storage->load($view_id)) {
      continue;
    }

    $config_name = 'views.view.' . $view_id;

    // Prefer the sync-directory record so the UUID matches a subsequent
    // `drush config:import`. Fall back to the module's optional config when
    // the site does not stage this view in sync.
    $record = NULL;
    if ($sync_storage->exists($config_name)) {
      $record = $sync_storage->read($config_name);
    }
    elseif ($optional_storage->exists($config_name)) {
      $record = $optional_storage->read($config_name);
    }
    if (!is_array($record)) {
      continue;
    }

    // config/optional files do not include a uuid; assign one explicitly so
    // the saved entity is valid and does not need a later patch update.
    if (empty($record['uuid'])) {
      $record['uuid'] = \Drupal::service('uuid')->generate();
    }

    $view = $views_storage->createFromStorageRecord($record);
    $view->save();
    $created = TRUE;
  }

  // A saved menu-tab view leaves views.view_route_names stale until the next
  // router rebuild, so local-task derivers (core's ViewsLocalTask and any site
  // override) emit "Undefined array key" warnings on the new view's tab
  // displays. Rebuild now to repopulate the map before any later local-task
  // derivation in this request.
  if ($created) {
    \Drupal::service('router.builder')->rebuild();
  }
}

/**
 * Drop thousands separator from ID fields in dashboard Views.
 */
function editoria11y_post_update_0002(): void {
  $id_field_keys = ['pid', 'pids', 'uid', 'uids', 'id', 'nid'];
  $view_ids = [
    'ed11y_result',
    'ed11y_action',
    'ed11y_export',
    'ed11y_crawler',
  ];

  $config_factory = \Drupal::configFactory();
  foreach ($view_ids as $view_id) {
    $config = $config_factory->getEditable('views.view.' . $view_id);
    if ($config->isNew()) {
      continue;
    }

    $displays = $config->get('display');
    if (!is_array($displays)) {
      continue;
    }

    $changed = FALSE;
    foreach ($displays as $display_id => $display) {
      $fields = $display['display_options']['fields'] ?? NULL;
      if (!is_array($fields)) {
        continue;
      }
      foreach ($fields as $field_key => $field) {
        if (!in_array($field_key, $id_field_keys, TRUE)) {
          continue;
        }
        if (($field['plugin_id'] ?? '') !== 'numeric') {
          continue;
        }
        if (($field['separator'] ?? '') === '') {
          continue;
        }
        $config->set("display.$display_id.display_options.fields.$field_key.separator", '');
        $changed = TRUE;
      }
    }

    if ($changed) {
      $config->save();
    }
  }
}

/**
 * Reset sandbox status filter to core node_status.
 */
function editoria11y_post_update_0003(): void {
  // The `ps_core_node_status` filter plugin is from Princeton's
  // sandbox and resolves to a broken handler in other environments.
  $view_ids = [
    'ed11y_result',
    'ed11y_action',
    'ed11y_export',
    'ed11y_crawler',
  ];

  $config_factory = \Drupal::configFactory();
  foreach ($view_ids as $view_id) {
    $config = $config_factory->getEditable('views.view.' . $view_id);
    if ($config->isNew()) {
      continue;
    }

    $displays = $config->get('display');
    if (!is_array($displays)) {
      continue;
    }

    $changed = FALSE;
    foreach ($displays as $display_id => $display) {
      $filters = $display['display_options']['filters'] ?? NULL;
      if (!is_array($filters)) {
        continue;
      }
      foreach ($filters as $filter_id => $filter) {
        if (($filter['plugin_id'] ?? '') !== 'ps_core_node_status') {
          continue;
        }
        $config->set("display.$display_id.display_options.filters.$filter_id.plugin_id", 'node_status');
        $changed = TRUE;
      }
    }

    if ($changed) {
      $config->save();
    }
  }
}

/**
 * Backfill ed11y_page.page_language to each referenced entity's own langcode.
 */
function editoria11y_post_update_0004(array &$sandbox): void {
  // Older records stored the negotiated page language (always a real site
  // language such as 'en') even when the referenced entity was language-neutral
  // ('und'/'zxx'), which stops the dashboard Views join from matching the
  // entity's langcode. Rewrite page_language to the entity's own langcode, but
  // only for rows whose stored value matches no translation of that entity, so
  // correct multilingual rows are left alone.
  //
  // The langcode is read straight from each entity's core data table rather
  // than by loading the entity: the pass only needs the langcode, and per-row
  // entity loads would grow the entity memory cache unbounded across batches
  // (cf. editoria11y_update_9019). A keyset cursor keeps it index-backed, and a
  // NOT EXISTS pre-filter means already-correct rows never leave the database.
  $database = \Drupal::database();
  $schema = $database->schema();
  $batch_size = 250;

  // route_name => [data table, id column] for each translatable content entity.
  $map = [
    'entity.node.canonical' => ['table' => 'node_field_data', 'id' => 'nid'],
    'entity.taxonomy_term.canonical' => ['table' => 'taxonomy_term_field_data', 'id' => 'tid'],
    'entity.user.canonical' => ['table' => 'users_field_data', 'id' => 'uid'],
  ];
  $routes = array_keys($map);
  $route_count = count($routes);

  if (!isset($sandbox['route_index'])) {
    $sandbox['route_index'] = 0;
    // Keyset cursor within the current route; reset when advancing routes.
    $sandbox['last_pid'] = 0;
    $sandbox['scanned'] = 0;
    $sandbox['fixed'] = 0;
  }

  // Skip entity types whose data table is not installed (e.g. the taxonomy
  // module was removed) so a missing table never aborts the update. tableExists
  // is statically cached, so re-checking each invocation is cheap.
  while ($sandbox['route_index'] < $route_count
    && !$schema->tableExists($map[$routes[$sandbox['route_index']]]['table'])) {
    $sandbox['route_index']++;
    $sandbox['last_pid'] = 0;
  }

  // All installed routes processed.
  if ($sandbox['route_index'] >= $route_count) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('Editoria11y')->notice(
      'editoria11y_post_update_0004: scanned @scanned mismatched page(s), backfilled @fixed to the referenced entity language.',
      ['@scanned' => $sandbox['scanned'], '@fixed' => $sandbox['fixed']]
    );
    return;
  }

  $route = $routes[$sandbox['route_index']];
  $table = $map[$route]['table'];
  $id_col = $map[$route]['id'];

  // Broken rows only: page_language matches no translation of the entity. The
  // INNER JOIN to the default-translation row both sources the corrected
  // langcode and confirms the entity still exists (deleted-entity rows are left
  // for the CSA "Check deleted" tool). $table/$id_col come from the static map
  // above, never user input.
  $sql = "SELECT p.pid, p.page_path, p.page_language AS current_lang, d.langcode AS correct_lang
    FROM {ed11y_page} p
    INNER JOIN {" . $table . "} d ON d." . $id_col . " = p.entity_id AND d.default_langcode = 1
    WHERE p.route_name = :route AND p.entity_id > 0 AND p.pid > :last_pid
      AND NOT EXISTS (
        SELECT 1 FROM {" . $table . "} m
        WHERE m." . $id_col . " = p.entity_id AND m.langcode = p.page_language
      )
    ORDER BY p.pid";
  $rows = $database->queryRange($sql, 0, $batch_size, [
    ':route' => $route,
    ':last_pid' => $sandbox['last_pid'],
  ])->fetchAll();

  // One transaction per batch: atomic, and avoids a commit per row. Committed
  // when $transaction is released below.
  $transaction = $database->startTransaction();
  $seen = 0;
  foreach ($rows as $row) {
    $seen++;
    $sandbox['scanned']++;
    // Advance the cursor for every row (including skips) so a skipped row is
    // never re-read into an infinite loop.
    $sandbox['last_pid'] = (int) $row->pid;

    if ($row->correct_lang === NULL || $row->correct_lang === $row->current_lang) {
      continue;
    }

    // Guard the {ed11y_page} unique key on (page_path, page_language): skip if
    // the corrected pair already exists on another row (the check sees this
    // batch's uncommitted updates too, so intra-batch collisions are handled).
    $collision = $database->select('ed11y_page', 'e')
      ->fields('e', ['pid'])
      ->condition('page_path', $row->page_path)
      ->condition('page_language', $row->correct_lang)
      ->condition('pid', $row->pid, '<>')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($collision) {
      continue;
    }

    // path_hash derives from (page_path, page_language), so recompute it with
    // the corrected language. hook_update_N runs before post-updates, so
    // editoria11y_update_9020() has added the column by the time this runs;
    // the guard only covers out-of-band invocations.
    $fields = ['page_language' => $row->correct_lang];
    if ($schema->fieldExists('ed11y_page', 'path_hash')) {
      $fields['path_hash'] = Api::pathHash($row->page_path, $row->correct_lang);
    }
    $database->update('ed11y_page')
      ->fields($fields)
      ->condition('pid', $row->pid)
      ->execute();
    $sandbox['fixed']++;
  }
  // Commit this batch.
  unset($transaction);

  // A short batch means this route is exhausted; advance to the next one. Never
  // key completion off a row count: the keyset cursor is what guarantees
  // forward progress as fixed rows drop out of the NOT EXISTS filter.
  if ($seen < $batch_size) {
    $sandbox['route_index']++;
    $sandbox['last_pid'] = 0;
  }

  $finished = $sandbox['route_index'] >= $route_count;
  $sandbox['#finished'] = $finished ? 1 : min(0.99, $sandbox['route_index'] / $route_count);

  if ($finished) {
    \Drupal::logger('Editoria11y')->notice(
      'editoria11y_post_update_0004: scanned @scanned mismatched page(s), backfilled @fixed to the referenced entity language.',
      ['@scanned' => $sandbox['scanned'], '@fixed' => $sandbox['fixed']]
    );
  }
}
