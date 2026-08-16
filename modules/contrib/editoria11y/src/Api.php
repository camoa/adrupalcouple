<?php

namespace Drupal\editoria11y;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\editoria11y\Exception\Editoria11yApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles reporting and dismissals.
 *
 * @phpstan-consistent-constructor
 */
class Api {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected PathValidatorInterface $pathValidator;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Constructs an Api object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\Core\State\StateInterface|null $state
   *   The state service.
   */
  public function __construct(AccountInterface $account, Connection $connection, ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, StateInterface $state) {
    $this->account = $account;
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->pathValidator = $path_validator;
    $this->state = $state;
  }

  /**
   * Creates an instance of the Api class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new instance of the Api class.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('current_user'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('state')
    );
  }

  /**
   * Computes the unique hash for a page path + language pair.
   *
   * The (page_path, page_language) pair is too long for a portable unique
   * key, so ed11y_page enforces uniqueness on this hash instead. Callers
   * must pass the normalized (truncated) values that get stored.
   */
  public static function pathHash(string $page_path, string $page_language): string {
    return hash('sha256', $page_path . '|' . $page_language);
  }

  /**
   * Builds the per-page dismissals cache tag for a path.
   *
   * The tag set at attach time (editoria11y_page_attachments) must match the
   * tag invalidated on writes, so every caller shares this helper.
   */
  public static function pathCacheTag(string $page_path): string {
    return 'editoria11y:dismissals_' . preg_replace('/[^a-zA-Z0-9]/', '', substr($page_path, -80));
  }

  /**
   * Get the pid ID.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Invalid data.
   */
  public function getPage($path, $entity_type, $entity_id, $language, $findStale = FALSE): ?StatementInterface {

    $this->validateNumber($entity_id);
    $this->validatePath($path);

    // Get back the page ID.
    $query = $this->connection->select('ed11y_page');
    $query->fields('ed11y_page', ['pid']);
    if ($findStale) {
      $query->condition('page_path', $path, '!=');
    }
    else {
      $query->condition('page_path', $path);
    }
    $query->condition('entity_id', $entity_id);
    $query->condition('entity_type', $entity_type);
    $query->condition('page_language', $language);
    return $query->execute();
  }

  /**
   * Set and return an pid ID.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Invalid data.
   */
  public function updatePage($results, $pid, $now): void {

    $this->validateNotNull($results["page_title"] ?? NULL);

    // @todo 3.0 can we multi-write?
    $update = $this->connection->update("ed11y_page");
    // Track the type and count of issues detected on this page.
    // Update the "last seen" date of the page.
    // entity_id is refreshed here so stored rows self-heal if the live
    // entity-detection logic ever resolves a path to a different id (e.g.
    // after a route-matching bug fix). Validated upstream in testResults().
    $update->fields(
      [
        'page_title' => $this->trimString($results["page_title"], 1024),
    // @todo separate dev count.
        'content_results' => $this->validateCount($results["content_total"] ?? NULL),
    // @todo separate dev count.
        'dev_results' => $this->validateCount($results["dev_total"] ?? NULL),
        'entity_id' => $this->validateCount($results["entity_id"] ?? NULL),
        'entity_type' => $this->trimString($results["entity_type"] ?? NULL, 32, 'unknown'),
        'route_name' => $this->trimString($results["route_name"] ?? NULL, 255, 'unknown'),
        'updated' => $now,
      ]
    );
    $update->condition('pid', $pid);

    $update->execute();
  }

  /**
   * Set and return an pid ID.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Validation errors.
   * @throws \Exception
   *    Invalid data.
   */
  public function insertPage($results, $now): int|string {

    $this->validateNotNull($results["page_title"] ?? NULL);
    $this->validatePath($results["page_path"]);

    // The stored values feed the path_hash unique key, so normalize them
    // once and hash exactly what is written.
    $page_path = mb_substr((string) $results["page_path"], 0, 1024);
    $page_language = $this->trimString($results["language"] ?? NULL, 64, 'unknown');

    // @todo 3.0 can we multi-write?
    $insert = $this->connection->insert("ed11y_page");
    // Track the type and count of issues detected on this page.
    $insert->fields(
      [
        'page_title' => $this->trimString($results["page_title"], 1024),
        'page_path' => $page_path,
        'path_hash' => static::pathHash($page_path, $page_language),
        'entity_id' => $this->validateCount($results["entity_id"] ?? NULL),
        'page_language' => $page_language,
        'content_results' => $this->validateCount($results["content_total"] ?? NULL),
        'dev_results' => $this->validateCount($results["dev_total"] ?? NULL),
        'entity_type' => $this->trimString($results["entity_type"] ?? NULL, 32, 'unknown'),
        'route_name' => $this->trimString($results["route_name"] ?? NULL, 255, 'unknown'),
        'updated' => $now,
      ]
    );

    // Get back the page ID.
    return $insert->execute();
  }

  /**
   * Inserts a page row, adopting an existing row for the same page.
   *
   * The path_hash unique key makes this insert throw when a row for the
   * same (page_path, page_language) already exists — either a concurrent
   * request won the race (two tabs, the crawler plus an editor), or the
   * caller's getPage() lookup missed because the stored entity metadata
   * differs from the current resolution (a recycled alias, a renamed
   * bundle). Roll back to a savepoint (required on PostgreSQL, where a
   * failed statement aborts the transaction), look the row up by the exact
   * invariant that collided, and adopt it: updatePage() refreshes
   * entity_id / entity_type / route_name to the current resolution, the
   * same self-heal applied on the normal update path.
   *
   * @return int|string|false
   *   The page ID, or FALSE if the colliding row vanished mid-request.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Invalid data.
   */
  private function insertPageSafe(array $data, int $now): int|string|FALSE {
    $savepoint = $this->connection->startTransaction();
    try {
      $pid = $this->insertPage($data, $now);
    }
    catch (IntegrityConstraintViolationException $e) {
      $savepoint->rollBack();
      $page_path = mb_substr((string) $data['page_path'], 0, 1024);
      $page_language = $this->trimString($data['language'] ?? NULL, 64, 'unknown');
      $pid = $this->connection->select('ed11y_page', 'p')
        ->fields('p', ['pid'])
        ->condition('path_hash', static::pathHash($page_path, $page_language))
        ->execute()
        ->fetchField();
      if ($pid) {
        $this->updatePage($data, $pid, $now);
      }
    }
    return $pid;
  }

  /**
   * Function to test the results.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Invalid data.
   */
  public function testResults($results): void {
    $now = time();

    $results = $this->normalizePageFields($results);
    $results['content_total'] = $this->validateCount($results['content_total'] ?? NULL);
    $results['dev_total'] = $this->validateCount($results['dev_total'] ?? NULL);
    if (!is_array($results['results'] ?? NULL) || !is_array($results['oks'] ?? NULL)) {
      throw new Editoria11yApiException("Missing results");
    }

    // A report is a multi-statement write (page upsert, result merges,
    // stale-row deletes, dismissal staleness updates). Run it atomically so
    // a mid-sequence failure cannot leave partial rows behind.
    $transaction = $this->connection->startTransaction();
    try {
      $this->processResults($results, $now);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Applies a validated report payload to the database.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *    Invalid data.
   */
  private function processResults(array $results, int $now): void {
    // Get any existing page record.
    $pid = $this->getPage($results["page_path"], $results["entity_type"], $results["entity_id"], $results["language"])->fetchField();
    $old_results = [];
    $new_results = [];
    $old_dismissals = FALSE;
    if ($pid) {
      // Stash existing information to reduce DB write-backs later. Two
      // targeted queries: a single join of results and actions would
      // multiply rows (results × actions) only to learn the same facts.
      $old_results = $this->connection->select('ed11y_result', 'r')
        ->fields('r', ['result_key'])
        ->condition('pid', $pid)
        ->execute()
        ->fetchCol();
      $first_dismissal = $this->connection->select('ed11y_action', 'd')
        ->fields('d', ['id'])
        ->condition('pid', $pid)
        ->range(0, 1)
        ->execute()
        ->fetchField();
      $old_dismissals = !empty($first_dismissal);
      $this->updatePage($results, $pid, $now);
    }
    elseif (count($results["results"]) > 0 || count($results["oks"]) > 0) {
      // There was no page at this address. Make a new one.
      $pid = $this->insertPageSafe($results, $now);
    }

    // Remove old results at this route.
    // Should we move this to the dashboard?
    // @todo 3.0 not yet tested
    $incorrectData = $this->getPage($results["page_path"], $results["entity_type"], $results["entity_id"], $results["language"], TRUE);
    $incorrectPage = $incorrectData->fetchField();
    if ($incorrectPage) {
      $this->purgePage($incorrectPage, $results["page_path"]);
    }
    if (!$pid) {
      // Nothing to report, nothing to remove.
      return;
    }

    // Update last seen.
    if ($results["dev_total"] + $results["content_total"] > 0) {
      foreach ($results["results"] as $key => $value) {
        $this->validateNotNull($key);
        $this->validateNotNull($value['result_name'] ?? NULL);
        $key = mb_substr((string) $key, 0, 255);
        $content_count = $this->validateCount($value['content_count'] ?? NULL);
        $dev_count = $this->validateCount($value['dev_count'] ?? NULL);
        $result_name = $this->trimString($value['result_name'], 255);
        // @todo 3.0: we need to handle page parameters that change content
        $new_results[] = $key;
        $updatePage = $this->connection->merge("ed11y_result");
        $updatePage->insertFields(
          [
            'content_count' => $content_count,
            'dev_count' => $dev_count,
            'result_name' => $result_name,
            'result_key' => $key,
            'created' => $now,
          ]
          );
        $updatePage->updateFields(
          [
            'content_count' => $content_count,
            'dev_count' => $dev_count,
            'result_name' => $result_name,
          ]
          );
        $updatePage->keys(
          [
            'pid' => $pid,
            'result_key' => $key,
          ]
          );
        $updatePage->execute();
      }
    }
    elseif (!$old_dismissals) {
      // Drop page with no remaining records.
      $this->purgePage($pid, $results['page_path']);
    }

    // Remove results no longer in the result set.
    $stale_results = array_diff($old_results, $new_results);
    if ($stale_results) {
      $this->connection->delete('ed11y_result')
        ->condition('result_key', $stale_results, 'IN')
        ->condition('pid', $pid)
        ->execute();
    }

    // Get pending okAll resets.
    $clears = $this->state->get('editoria11y.pending_clear', '');
    $clear_ids = empty($clears) ? [] : explode("|", $clears);

    // Update stale dates.
    // Marked-as-ok issues are not in the main results foreach.
    // Note: v2.1.0 added entity_id; old entries may be missing it.
    // Note: v2.2.10 changed entity type; old entries have a different format.
    if ($old_dismissals) {
      foreach ($results["oks"] as $ok) {
        // @todo 3.x: need to handle okAll differently depending on if it is from its origin page.
        if ($ok["action_type"] === "ok" && !in_array($ok["resultKey"], $new_results)) {
          $new_results[] = $ok["resultKey"];
        }
        if ($ok["action_type"] === "okAll" &&
          !in_array($ok["resultKey"], $new_results) &&
          !in_array($ok["resultKey"], $clear_ids)
        ) {
          $new_results[] = $ok["resultKey"];
        }
      }
      if (count($new_results) > 0) {
        $this->connection->update('ed11y_action')
          ->fields(['stale_date' => NULL])
          ->condition('result_key', $new_results, 'IN')
          ->condition('pid', $pid)
          ->execute();
        // Set stale records if the alert disappeared.
        $this->connection->update('ed11y_action')
          ->fields(['stale_date' => $now])
          ->condition('result_key', $new_results, 'NOT IN')
          ->condition('pid', $pid)
          ->isNull('stale_date')
          ->execute();
      }
      else {
        // If there are no new results, mark all old dismissals as stale.
        $this->connection->update('ed11y_action')
          ->fields(['stale_date' => $now])
          ->condition('pid', $pid)
          ->isNull('stale_date')
          ->execute();
      }

    }

    // Check the parsed queue; a queued hash starting with "0"
    // would evaluate empty() and stall the queue permanently.
    if (!empty($clear_ids)) {
      $clear_id = $clear_ids[0];
      $clearBatch = $this->state->get('editoria11y.pending_clear_batch', 1);
      $okAllCount = $this->connection->select('ed11y_action')
        ->condition('element_id', $clear_id)
        ->condition('action_type', 'okAll')
        ->countQuery()
        ->execute()
        ->fetchField();

      if ((int) $okAllCount === 0) {
        array_splice($clear_ids, 0, 1);
        $this->state->set('editoria11y.pending_clear_batch', 1);
        $this->state->set('editoria11y.pending_clear', implode('|', $clear_ids));
      }
      else {
        $topPid = $this->connection->select('ed11y_action')
          ->orderBy('pid', 'DESC')
          ->fields('ed11y_action', ['pid'])
          ->range(0, 1)
          ->execute()
          ->fetchField();
        $batches = floor($okAllCount / 1000) + 1;
        $pidsPerBatch = ceil($topPid / $batches);
        $this->connection->delete("ed11y_action")
          ->condition('element_id', $clear_id)
          ->condition('action_type', 'okAll')
          ->condition('pid', $pidsPerBatch * ($clearBatch), '<')
          ->execute();
        $this->state->set('editoria11y.pending_clear_batch', $clearBatch + 1);
      }
    }
  }

  /**
   * The Purge page function.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  public function purgePage($page = FALSE, $path = FALSE): void {
    // Internal functions provide path, direct calls do not.
    if ($page) {
      $page = $this->validateCount($page);
      $path = $this->connection->select("ed11y_page")
        ->fields('ed11y_page', ['page_path'])
        ->condition('pid', $page)
        ->execute()->fetchField();
    }
    elseif ($path) {
      $this->validateNotNull($path);
      // Get back the page ID.
      $query = $this->connection->select('ed11y_page');
      $query->fields('ed11y_page', ['pid']);
      $query->condition('page_path', $path);
      $page = $query->execute()->fetchField();
    }
    if ($page && $path) {
      // Delete the page and its children atomically so a failure cannot
      // leave orphaned result or action rows behind.
      $transaction = $this->connection->startTransaction();
      try {
        $this->connection->delete("ed11y_action")
          ->condition('pid', $page)
          ->execute();
        $this->connection->delete("ed11y_result")
          ->condition('pid', $page)
          ->execute();
        $this->connection->delete("ed11y_page")
          ->condition('pid', $page)
          ->execute();
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
      }
      // Clear cache for the referring page and dashboard.
      Cache::invalidateTags([static::pathCacheTag($path)]);
    }
  }

  /**
   * The purge dismissal function.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  public function purgeDismissal($data): void {
    $page_path = FALSE;
    if ($data['dismissal_id'] ?? FALSE) {
      $this->validateCount($data['dismissal_id']);
      $this->validateCount($data['pid'] ?? NULL);
      $page_path = $this->connection->select('ed11y_page')
        ->fields('ed11y_page', ['page_path'])
        ->condition('pid', $data['pid'])
        ->execute()->fetchField();
      if (!empty($page_path)) {
        $this->connection->delete("ed11y_action")
          ->condition('pid', $data['pid'])
          ->condition('id', $data["dismissal_id"])
          ->execute();
      }
    }
    elseif (($data['page_path'] ?? FALSE) && ($data['uid'] ?? FALSE)) {
      $this->validateCount($data['pid'] ?? NULL);
      $this->validateNotNull($data['entity_type']);
      $this->validateNotNull($data['entity_id']);
      $this->validateNotNull($data['language']);
      $element_id = $this->validateElementId($data['element_id'] ?? NULL);
      $this->validateNotNull($data['result_key'] ?? NULL);
      $page = $this->getPage($data['page_path'], $data['entity_type'], $data['entity_id'], $data['language'])->fetchField();
      if ($page) {
        $this->connection->delete("ed11y_action")
          ->condition('pid', $data['pid'])
          ->condition('element_id', $element_id)
          ->condition('result_key', $data["result_key"])
          ->execute();
        $page_path = $data['page_path'];
      }
    }
    // Clear cache for the referring page.
    if (!empty($page_path)) {
      Cache::invalidateTags([static::pathCacheTag($page_path)]);
    }
  }

  /**
   * The dismiss function.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   * @throws \Exception
   *   Invalid data.
   */
  public function dismiss($dismissal): void {
    $dismissal = $this->normalizePageFields($dismissal);
    if (!is_array($dismissal['dismissals'] ?? NULL)) {
      throw new Editoria11yApiException("Missing dismissals");
    }
    $now = time();

    // Dismissals are also multi-statement (page insert, per-item deletes and
    // inserts, state queue updates); run atomically like testResults().
    $transaction = $this->connection->startTransaction();
    try {
      $this->processDismissal($dismissal, $now);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Applies a validated dismissal payload to the database.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function processDismissal(array $dismissal, int $now): void {
    $get_page = $this->getPage($dismissal['page_path'], $dismissal['entity_type'], $dismissal['entity_id'], $dismissal['language']);
    $pid = $get_page->fetchField();
    if (!$pid) {
      // Theoretically a dynamic update could send a dismissal as a new record.
      $pid = $this->insertPageSafe($dismissal, $now);
    }

    $this->validateNumber($pid);

    $clears = $this->state->get('editoria11y.pending_clear', '');
    $clear_ids = empty($clears) ? [] : explode("|", $clears);
    $write_state = FALSE;
    $reset_batch = FALSE;

    foreach ($dismissal['dismissals'] as $item) {
      $operation = $item['action_type'] ?? '';
      $this->validateDismissalType($operation);
      $this->validateDismissalPermission($operation);
      $element_id = $this->validateElementId($item['element_id'] ?? NULL);
      $this->validateNotNull($item['result_key'] ?? NULL);
      $result_key = mb_substr((string) $item['result_key'], 0, 255);
      if ($operation === 'reset') {
        if ($this->account->hasPermission('mark as ok in editoria11y')) {
          // Reset all for this result.
          if (!in_array($element_id, $clear_ids)) {
            // Add to batch queue to clear dismissal from other pages.
            $clear_ids[] = $element_id;
            $write_state = TRUE;
          }
          $this->connection->delete("ed11y_action")
            ->condition('pid', $pid)
            ->condition('result_key', $result_key)
            ->condition('element_id', $element_id)
            ->condition('action_type', ['ok', 'okAll'], 'IN')
            ->execute();
        }
        if ($this->account->hasPermission('mark as hidden in editoria11y')) {
          $this->connection->delete("ed11y_action")
            ->condition('pid', $pid)
            ->condition('action_type', "hide")
            ->condition('uid', $this->account->id())
            ->condition('element_id', $element_id)
            ->condition('result_key', $result_key)
            ->execute();
        }
      }
      else {
        $this->validateNotNull($item["result_name"] ?? NULL);

        // Merge on the dismissal's unique identity so a re-sent dismissal
        // (double click, second tab) refreshes the row instead of throwing
        // on the ed11y_action unique key.
        $this->connection->merge("ed11y_action")
          ->insertFields(
            [
              'result_name' => $this->trimString($item["result_name"], 255),
              'created' => $now,
            ]
          )
          ->updateFields(
            [
              'result_name' => $this->trimString($item["result_name"], 255),
              'created' => $now,
              'stale_date' => NULL,
            ]
          )
          ->keys(
            [
              'pid' => $pid,
              'uid' => $this->account->id(),
              'element_id' => $element_id,
              'result_key' => $result_key,
              'action_type' => $operation,
            ]
          )
          ->execute();

        if ($operation === 'okAll') {
          // Remove from batch queue if this was recently reset.
          $arrayPos = array_search($element_id, $clear_ids);
          if ($arrayPos !== FALSE) {
            array_splice($clear_ids, $arrayPos, 1);
            $write_state = TRUE;
            if ($arrayPos === 0) {
              $reset_batch = TRUE;
            }
          }
          else {
            Cache::invalidateTags(
              ['editoria11y:dismissals']
            );
            // Bust browser cache for the config API endpoint.
            $v = $this->state->get('editoria11y.config_version', 0);
            $this->state->set('editoria11y.config_version', $v + 1);
          }
        }
      }
    }

    if ($write_state) {
      Cache::invalidateTags(
        ['editoria11y:dismissals']
      );
      // Bust browser cache for the config API endpoint.
      $v = $this->state->get('editoria11y.config_version', 0);
      $this->state->set('editoria11y.config_version', $v + 1);
      if (!empty($clear_ids) && $clear_ids[0] === '') {
        array_splice($clear_ids, 0, 1);
        $reset_batch = TRUE;
      }
      if ($reset_batch) {
        $this->state->set('editoria11y.pending_clear_batch', 0);
      }
      $this->state->set('editoria11y.pending_clear', implode("|", $clear_ids));
    }
    // Clear cache for the referring page and dashboard.
    Cache::invalidateTags([static::pathCacheTag($dismissal["page_path"])]);
  }

  /**
   * Throw exception for missing values.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validateNotNull($user_input): void {
    if (empty($user_input)) {
      throw new Editoria11yApiException("Missing value");
    }
  }

  /**
   * Coerces client input to a non-negative database integer.
   *
   * Is_numeric() alone admits floats, negatives and values beyond the int
   * column range, which either corrupt counts or throw driver exceptions.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validateCount($user_input): int {
    if (!is_numeric($user_input)) {
      throw new Editoria11yApiException("Nan: $user_input");
    }
    $int = (int) $user_input;
    if ($int < 0 || $int > 2147483647) {
      throw new Editoria11yApiException("Number out of range: $int");
    }
    return $int;
  }

  /**
   * Trims a descriptive string to its column length.
   *
   * Descriptive fields (titles, labels, route names) should never fail a
   * whole report over length, so they are truncated rather than rejected;
   * empty values fall back to the column default.
   */
  private function trimString($user_input, int $length, string $fallback = ''): string {
    $value = trim((string) ($user_input ?? ''));
    if ($value === '') {
      $value = $fallback;
    }
    return mb_substr($value, 0, $length);
  }

  /**
   * Validates an element id (a hash generated by the checker JS).
   *
   * The column is char(64); reject anything that is not short printable
   * ASCII rather than letting the database truncate or error.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validateElementId($user_input): string {
    if (!is_string($user_input) || !preg_match('/^[\x21-\x7E]{1,64}$/', $user_input)) {
      throw new Editoria11yApiException("Invalid element id");
    }
    return $user_input;
  }

  /**
   * Normalizes the shared page fields of a report or dismissal payload.
   *
   * Runs before any lookup: getPage()/insertPage() use these fields both to
   * find and to write rows, so truncation and coercion must happen once,
   * up front, or a lookup could miss the row a later write creates.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function normalizePageFields(array $data): array {
    $data['page_path'] = mb_substr((string) ($data['page_path'] ?? ''), 0, 1024);
    $this->validatePath($data['page_path']);
    $data['entity_id'] = $this->validateCount($data['entity_id'] ?? NULL);
    $data['language'] = $this->trimString($data['language'] ?? NULL, 64, 'unknown');
    $data['entity_type'] = $this->trimString($data['entity_type'] ?? NULL, 32, 'unknown');
    $data['route_name'] = $this->trimString($data['route_name'] ?? NULL, 255, 'unknown');
    if (array_key_exists('page_title', $data)) {
      $data['page_title'] = $this->trimString($data['page_title'], 1024);
    }
    return $data;
  }

  /**
   * Throw exception if user does not have permission for this dismissal type.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid role.
   */
  private function validateDismissalPermission($operation): void {
    $canHide = $this->account->hasPermission('mark as hidden in editoria11y');
    $canOk = $this->account->hasPermission('mark as ok in editoria11y');
    $hides = ['hide', 'reset'];
    $oks = ['ok', 'okAll', 'reset'];
    if (
      !(
        ($canHide && in_array($operation, $hides)) ||
        ($canOk && in_array($operation, $oks))
      )
    ) {
      throw new Editoria11yApiException("Not allowed");
    }
  }

  /**
   * This function is used to validate the requested path.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validatePath($user_input): void {
    $config = $this->configFactory->get('editoria11y.settings');
    $prefix = $config->get('redundant_prefix');
    if (!empty($prefix) && strlen($prefix) < strlen($user_input) && str_starts_with($user_input, $prefix)) {
      // Replace ignorable subfolders.
      $altPath = substr_replace($user_input, "", 0, strlen($prefix));
      if (
        !(
          $this->pathValidator->getUrlIfValid($altPath) ||
          $this->pathValidator->getUrlIfValid($user_input)
        )
      ) {
        throw new Editoria11yApiException('Invalid page path on API report: "' . $user_input . '". If site is installed in subfolder, check Editoria11y config item "Syncing results to reports
--> Remove redundant base url from URLs"');
      }
    }
    else {
      if (!$this->pathValidator->getUrlIfValid($user_input)) {
        throw new Editoria11yApiException('Invalid page path on API report: "' . $user_input . '". If site is installed in subfolder, check Editoria11y config item "Syncing results to reports
--> Remove redundant base url from URLs"');
      }
    }
  }

  /**
   * Validate dismissal status function.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validateDismissalType(string $user_input): void {
    if (!(in_array($user_input, ['ok', 'okAll', 'hide', 'reset']))) {
      throw new Editoria11yApiException("Invalid dismissal operation: $user_input");
    }
  }

  /**
   * Validate number function.
   *
   * @throws \Drupal\editoria11y\Exception\Editoria11yApiException
   *   Invalid data.
   */
  private function validateNumber($user_input): void {
    if (!(is_numeric($user_input))) {
      throw new Editoria11yApiException("Nan: $user_input");
    }
  }

}
