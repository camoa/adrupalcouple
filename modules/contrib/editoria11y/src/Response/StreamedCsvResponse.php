<?php

namespace Drupal\editoria11y\Response;

use Drupal\Core\Session\ResponseKeepSessionOpenInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A streamed response whose session stays open while content streams.
 *
 * The CSV export callback re-executes a View for each result window, and
 * building the view's exposed filter form reads and writes the session (form
 * state, the CSRF token seed). The session middleware normally closes the
 * session before a response is sent, and re-opening one from inside a
 * streamed callback throws a RuntimeException ("headers have already been
 * sent") on any server that sends output unbuffered, such as Apache with
 * mod_php; PHP-FPM's default output buffer masks the problem locally.
 * Implementing ResponseKeepSessionOpenInterface makes the middleware leave
 * the session open instead, and CsvExportController saves it once streaming
 * completes. BigPipe streams exposed-form content under the same constraint
 * with the same interface, which core marks @internal but provides no
 * alternative to.
 *
 * @see \Drupal\Core\StackMiddleware\Session::handle()
 * @see \Drupal\big_pipe\Render\BigPipeResponse
 * @see \Drupal\editoria11y\Controller\CsvExportController::export()
 */
final class StreamedCsvResponse extends StreamedResponse implements ResponseKeepSessionOpenInterface {}
