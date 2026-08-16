<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\editoria11y\Response\StreamedCsvResponse;
use Drupal\editoria11y\ViewsCsvExporter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a dashboard View display as a CSV download.
 */
final class CsvExportController extends ControllerBase {

  /**
   * Exporter property.
   *
   * @var \Drupal\editoria11y\ViewsCsvExporter
   */
  protected ViewsCsvExporter $exporter;

  /**
   * Request stack property.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a CsvExportController object.
   *
   * @param \Drupal\editoria11y\ViewsCsvExporter $exporter
   *   Exporter property.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack property.
   */
  public function __construct(ViewsCsvExporter $exporter, RequestStack $request_stack) {
    $this->exporter = $exporter;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('editoria11y.views_csv_exporter'),
      $container->get('request_stack'),
    );
  }

  /**
   * Streams the requested view display as a CSV attachment.
   *
   * Query parameters are passed through to the view as exposed filter input,
   * so the download matches what the user had filtered on screen; view_args
   * carries contextual filter values for displays with path arguments.
   *
   * @param string $view_id
   *   The view machine name.
   * @param string $display_id
   *   The display machine name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   The CSV download.
   */
  public function export(string $view_id, string $display_id, Request $request): StreamedResponse {
    $exposed = $request->query->all();
    unset($exposed['view_args'], $exposed['page'], $exposed['q']);

    $args = [];
    foreach ($request->query->all('view_args') as $arg) {
      if (is_scalar($arg)) {
        $args[] = (string) $arg;
      }
    }

    $this->exporter->validate($view_id, $display_id, $this->currentUser());

    $response = new StreamedCsvResponse(function () use ($view_id, $display_id, $args, $exposed, $request): void {
      // Symfony pops the request before a streamed callback runs and only
      // recent releases re-push it around the callback; view execution needs
      // one on the stack (exposed form building, query-parameter argument
      // defaults). A balanced duplicate push is harmless either way.
      $this->requestStack->push($request);
      try {
        if (function_exists('set_time_limit')) {
          set_time_limit(0);
        }
        $out = fopen('php://output', 'wb');
        if ($out === FALSE) {
          return;
        }
        // Byte-order mark: without it, spreadsheet applications misread
        // accented characters in UTF-8 CSV files.
        fwrite($out, "\xEF\xBB\xBF");
        $rows_since_flush = 0;
        foreach ($this->exporter->rows($view_id, $display_id, $args, $exposed) as $row) {
          // Empty escape argument: RFC-style enclosure doubling.
          fputcsv($out, $row, ',', '"', '');
          $rows_since_flush++;
          if ($rows_since_flush >= ViewsCsvExporter::CHUNK_SIZE) {
            $rows_since_flush = 0;
            fflush($out);
            if (ob_get_level() > 0) {
              ob_flush();
            }
            flush();
          }
        }
        fclose($out);
      }
      finally {
        // StreamedCsvResponse keeps the session middleware from closing the
        // session before this callback runs (re-opening a closed session
        // mid-stream fails once output has been sent), so it is still open
        // here and must be saved manually -- and before the pop below: a
        // session left for PHP to write at request shutdown fatals in
        // SessionHandler::doWrite() on the missing request, appending an
        // error page to the CSV. Core's BigPipe closes the session the same
        // way after streaming.
        // @see \Drupal\big_pipe\Render\BigPipe::performPostSendTasks()
        if ($request->hasSession() && $request->getSession()->isStarted()) {
          $request->getSession()->save();
        }
        $this->requestStack->pop();
      }
    });

    $filename = 'editoria11y-' . $view_id . '-' . $display_id . '-' . date('Y-m-d') . '.csv';
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set(
      'Content-Disposition',
      $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
    );
    return $response;
  }

}
