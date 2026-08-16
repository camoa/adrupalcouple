<?php

namespace Drupal\editoria11y\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\editoria11y\ViewsCsvExporter;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ResultRow;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Render a field as a link to the pages by issue view.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("editoria11y_issues_by_page_link")
 */
class IssuesByPageLink extends Standard {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): MarkupInterface|string|ViewsRenderPipelineMarkup|GeneratedLink {
    $value = parent::render($values);
    if (isset($values->ed11y_page_ed11y_result_pid)) {
      $pid = $values->ed11y_page_ed11y_result_pid;
    }
    elseif (isset($values->pid)) {
      $pid = $values->pid;
    }
    else {
      $pid = FALSE;
    }
    if (!empty($value) && $pid) {
      // During CSV export, emit the plain count: the export reduces links to
      // their text anyway, and per-row URL generation costs time and memory.
      // @phpstan-ignore-next-line
      $request = \Drupal::service('request_stack')->getCurrentRequest();
      if ($request !== NULL && $request->attributes->get(ViewsCsvExporter::EXPORT_REQUEST_ATTRIBUTE)) {
        return $value;
      }

      $label = $value;
      // Build from the route rather than Url::fromUserInput(): the path form
      // routes a router match per row, which both costs time and retains
      // several KB per call for the rest of the request — enough to exhaust
      // PHP memory when the CSV exporter renders tens of thousands of rows.
      $url = Url::fromRoute('view.ed11y_result.issues_by_page__page', [], [
        'query' => [
          'id' => $pid,
        ],
      ]);

      try {
        $value = Link::fromTextAndUrl($label, $url)->toString();
      }
      catch (RouteNotFoundException) {
        // The dashboard view or display was removed; keep the plain value.
      }
    }

    return $value;
  }

}
