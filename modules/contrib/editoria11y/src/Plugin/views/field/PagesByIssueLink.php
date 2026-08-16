<?php

namespace Drupal\editoria11y\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\editoria11y\TestNames;
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
 * @ViewsField("editoria11y_pages_by_issue_link")
 */
class PagesByIssueLink extends Standard {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Guarantee result_name is joined regardless of how the display is
    // configured, so the fallback in render() works for custom test keys
    // not present in TestNames::coreNames().
    //
    // On aggregated displays (group_by: true) a plain column would land in
    // GROUP BY, and because the same result_key can have different stored
    // result_name values, that would fan out to duplicate rows. Wrap it in
    // MAX() so the query returns one representative name per result_key.
    if ($this->view->display_handler->getOption('group_by')) {
      $this->additional_fields['result_name'] = [
        'field' => 'result_name',
        'params' => ['function' => 'max'],
      ];
    }
    else {
      $this->additional_fields['result_name'] = 'result_name';
    }
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values): MarkupInterface|string|ViewsRenderPipelineMarkup|GeneratedLink {
    $value = parent::render($values);
    $label = $value;
    if (!empty($value)) {

      $key = is_object($value) && method_exists($value, '__toString') ? $value->__toString() : (string) $value;
      $translated = (new TestNames())->coreNames();
      if (isset($translated[$key])) {
        $label = $translated[$key];
      }
      else {
        // result_name is untrusted: it is written by the JS client via POST.
        // Reject non-strings and let Drupal's Link renderer escape strings
        // when building the anchor text.
        $alias = $this->aliases['result_name'] ?? NULL;
        $raw = $alias !== NULL ? ($values->{$alias} ?? NULL) : NULL;
        if (is_string($raw) && $raw !== '') {
          $label = $raw;
        }
      }

      // During CSV export, emit the resolved label: the export reduces links
      // to their text anyway, and per-row URL generation costs time and
      // memory.
      // @phpstan-ignore-next-line
      $request = \Drupal::service('request_stack')->getCurrentRequest();
      if ($request !== NULL && $request->attributes->get(ViewsCsvExporter::EXPORT_REQUEST_ATTRIBUTE)) {
        return $label;
      }

      // Build from the route rather than Url::fromUserInput(): the path form
      // routes a router match per row, which both costs time and retains
      // several KB per call for the rest of the request — enough to exhaust
      // PHP memory when the CSV exporter renders tens of thousands of rows.
      $url = Url::fromRoute('view.ed11y_result.pages_by_issue__page', [], [
        'query' => [
          'alert' => $key,
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
