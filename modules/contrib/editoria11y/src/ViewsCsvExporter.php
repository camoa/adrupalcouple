<?php

namespace Drupal\editoria11y;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds CSV rows from an Editoria11y dashboard View display.
 *
 * Columns are derived from whatever non-excluded fields the display has at
 * request time, so site-builder edits to a View are always reflected in its
 * export. Results are fetched in fixed-size windows so memory stays flat on
 * large result tables; the whole export still runs in a single request, so
 * exports large enough to outlast a proxy or FPM timeout should use the
 * batch-based editoria11y_export submodule instead.
 */
class ViewsCsvExporter {

  /**
   * The base tables of the module's Views; only these may be exported.
   */
  public const ALLOWED_BASE_TABLES = [
    'ed11y_page',
    'ed11y_result',
    'ed11y_action',
  ];

  /**
   * Rows fetched per query window.
   */
  public const CHUNK_SIZE = 500;

  /**
   * Leading characters that spreadsheet applications execute as formulas.
   *
   * Mirrors \League\Csv\EscapeFormula::FORMULA_STARTING_CHARS so this module
   * and the wider PHP CSV ecosystem agree on the trigger set.
   */
  public const FORMULA_TRIGGER_CHARS = ['=', '-', '+', '@', "\t", "\r"];

  /**
   * Request attribute marking an in-progress CSV export.
   *
   * The module's link field handlers return their plain text when this is
   * set: the export reduces links to text anyway, and per-row URL generation
   * for routed paths retains several KB per call in the router for the rest
   * of the request, which would exhaust PHP memory on large exports.
   */
  public const EXPORT_REQUEST_ATTRIBUTE = '_editoria11y_csv_export';

  /**
   * Entity type manager property.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Module handler property.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Entity memory cache property.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface
   */
  protected MemoryCacheInterface $entityMemoryCache;

  /**
   * Request stack property.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a ViewsCsvExporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager property.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler property.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $entity_memory_cache
   *   Entity memory cache property.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack property.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, MemoryCacheInterface $entity_memory_cache, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityMemoryCache = $entity_memory_cache;
    $this->requestStack = $request_stack;
  }

  /**
   * Checks that a view display exists, is exportable, and is accessible.
   *
   * The base-table check keeps this from becoming an arbitrary-view export
   * endpoint: only Views over the module's own tables qualify, which also
   * covers site-builder clones of the shipped Views. Data joined in from
   * other tables (titles, authors) is only reachable when the account can
   * already see the display itself, checked via the view's own access plugin.
   *
   * @param string $view_id
   *   The view machine name.
   * @param string $display_id
   *   The display machine name.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting the export.
   *
   * @return \Drupal\views\ViewExecutable
   *   The validated executable, set to the requested display.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When the view or display does not exist.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When the view is not exportable or the account may not see the display.
   */
  public function validate(string $view_id, string $display_id, AccountInterface $account): ViewExecutable {
    $view_entity = $this->entityTypeManager->getStorage('view')->load($view_id);
    if (!$view_entity instanceof ViewEntityInterface) {
      throw new NotFoundHttpException();
    }
    if (!in_array($view_entity->get('base_table'), self::ALLOWED_BASE_TABLES, TRUE)) {
      throw new AccessDeniedHttpException();
    }

    $executable = $view_entity->getExecutable();
    $display_set = $executable->setDisplay($display_id);
    if (!$display_set || $executable->current_display !== $display_id) {
      throw new NotFoundHttpException();
    }
    if (!$executable->display_handler->isEnabled() || !$executable->access($display_id, $account)) {
      throw new AccessDeniedHttpException();
    }
    return $executable;
  }

  /**
   * Generates CSV rows for a view display, header row first.
   *
   * Each chunk executes on a fresh executable with the display's pager
   * replaced by a fixed window, so no count query runs and any pager
   * configuration (including "display all") is exported in full.
   *
   * @param string $view_id
   *   The view machine name. Call validate() before streaming.
   * @param string $display_id
   *   The display machine name.
   * @param array $args
   *   Contextual filter values, in argument order.
   * @param array $exposed
   *   Exposed filter input, keyed by filter identifier. When empty, the view
   *   falls back to reading the current request's query parameters itself
   *   (setExposedInput([]) would not stick: getExposedInput() treats an empty
   *   array as unset), which yields the same result for the export route.
   * @param int $chunk
   *   Rows per query window.
   *
   * @return \Generator
   *   Yields one array of plain-text cell values per CSV row, with
   *   spreadsheet formula triggers escaped.
   */
  public function rows(string $view_id, string $display_id, array $args, array $exposed, int $chunk = self::CHUNK_SIZE): \Generator {
    $view_entity = $this->entityTypeManager->getStorage('view')->load($view_id);
    if (!$view_entity instanceof ViewEntityInterface) {
      throw new NotFoundHttpException();
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $request->attributes->set(self::EXPORT_REQUEST_ATTRIBUTE, TRUE);
    }

    $view = $view_entity->getExecutable();
    $offset = 0;
    $header_sent = FALSE;
    do {
      // destroy() resets the executable to its default state, so the same
      // instance can be re-configured and re-executed for each window.
      $view->destroy();
      $view->setDisplay($display_id);
      $view->display_handler->setOption('pager', [
        'type' => 'some',
        'options' => [
          'items_per_page' => $chunk,
          'offset' => $offset,
        ],
      ]);
      if ($args !== []) {
        $view->setArguments($args);
      }
      if ($exposed !== []) {
        $view->setExposedInput($exposed);
      }
      $view->preExecute();
      $view->execute();
      if (!empty($view->build_info['fail']) || !empty($view->build_info['denied'])) {
        break;
      }

      // Mirror the parts of ViewExecutable::render() that shape field output:
      // field preRender() (entity_link and entity-field handlers batch-load
      // their entities there) and hook_views_pre_render() (which is where the
      // module hides the language column on single-language sites). Area
      // handlers and theme-layer hooks are deliberately not run.
      $view->initStyle();
      foreach (array_keys($view->field) as $field_id) {
        $view->field[$field_id]->preRender($view->result);
      }
      $view->style_plugin->preRender($view->result);
      $this->moduleHandler->invokeAll('views_pre_render', [$view]);

      $field_ids = [];
      foreach ($view->field as $field_id => $handler) {
        if (empty($handler->options['exclude'])) {
          $field_ids[] = $field_id;
        }
      }

      if (!$header_sent) {
        $header = [];
        foreach ($field_ids as $field_id) {
          $label = (string) $view->field[$field_id]->label();
          $header[] = $this->escapeSpreadsheetFormula($label === '' ? $field_id : $label);
        }
        yield $header;
        $header_sent = TRUE;
      }

      $count = count($view->result);
      foreach (array_keys($view->result) as $index) {
        $row = [];
        foreach ($field_ids as $field_id) {
          $text = $this->toPlainText($view->style_plugin->getField($index, $field_id));
          $row[] = $this->escapeSpreadsheetFormula($text);
        }
        yield $row;
      }

      // Entities batch-loaded for field rendering would otherwise accumulate
      // in the static entity cache across windows.
      $this->entityMemoryCache->deleteAll();
      $offset += $chunk;
    } while ($count === $chunk);
    $view->destroy();
  }

  /**
   * Converts rendered field markup to a single-line plain-text CSV cell.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $value
   *   A rendered field value from the view's style plugin.
   *
   * @return string
   *   Plain text with entities decoded and whitespace collapsed. Links are
   *   reduced to their text, matching the field mapping the Views Data Export
   *   configuration shipped for the same columns.
   */
  public function toPlainText(MarkupInterface|string|null $value): string {
    if ($value === NULL) {
      return '';
    }
    $text = PlainTextOutput::renderFromHtml((string) $value);
    // \s misses the non-breaking spaces themes leave behind in cell markup.
    $collapsed = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);
    if ($collapsed === NULL) {
      $collapsed = $text;
    }
    return trim($collapsed);
  }

  /**
   * Prefixes an apostrophe when a cell would execute as a spreadsheet formula.
   *
   * Excel and LibreOffice evaluate cell text beginning with "=", "-", "+",
   * "@", tab, or carriage return when a CSV is opened. Titles and display
   * names in these exports are written by accounts with less access than the
   * report viewers who open them, so an unescaped cell would let a content
   * editor plant a formula that runs on a reviewer's machine. The apostrophe
   * is the OWASP-recommended neutralizer: spreadsheets treat it as a
   * text-format marker and hide it; other consumers see a literal quote.
   *
   * Public so the editoria11y_export submodule can apply the same escaping to
   * its Views Data Export rows.
   *
   * @param string $value
   *   A finished plain-text cell value.
   *
   * @return string
   *   The value, apostrophe-prefixed when it starts with a trigger character.
   *
   * @see https://owasp.org/www-community/attacks/CSV_Injection
   */
  public function escapeSpreadsheetFormula(string $value): string {
    if ($value !== '' && in_array($value[0], self::FORMULA_TRIGGER_CHARS, TRUE)) {
      return "'" . $value;
    }
    return $value;
  }

}
