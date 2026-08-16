<?php

namespace Drupal\editoria11y\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\editoria11y\ViewsCsvExporter;
use Drupal\views\Plugin\views\field\Standard;
use Drupal\views\ResultRow;

/**
 * Render a value to the page.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("editoria11y_page_link")
 */
class PageLink extends Standard {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = parent::render($values);
    if (empty($value) || !property_exists($values, 'ed11y_page_ed11y_result_items_page_path')) {
      return $value;
    }

    // During CSV export, emit the plain title: the export reduces links to
    // their text anyway, and per-row URL generation for routed paths retains
    // several KB in the router for the rest of the request, which would
    // exhaust PHP memory on large exports.
    // @phpstan-ignore-next-line
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($request !== NULL && $request->attributes->get(ViewsCsvExporter::EXPORT_REQUEST_ATTRIBUTE)) {
      return $value;
    }

    $path = $values->ed11y_page_ed11y_result_items_page_path;

    // @phpstan-ignore-next-line
    $config = \Drupal::config('editoria11y.settings');
    $prefix = $config->get('redundant_prefix');
    if (!empty($prefix)) {
      // Replace first instance.
      $pos = strpos($path, $prefix);
      if ($pos !== FALSE) {
        $path = substr_replace($path, "", $pos, strlen($prefix));
      }
    }

    // Multilingual validation is a pain and a performance concern:
    // https://www.drupal.org/project/drupal/issues/2994575#comment-14863919
    // $url = \Drupal::service('path.validator')->getUrlIfValidWithoutAccessCheck($path);
    $url = Url::fromUserInput($path);
    if (!$url) {
      return $value . ' ' . $this->t('(invalid URL)');
    }

    $url->mergeOptions(['query' => ['ed1ref' => $path]]);

    return Link::fromTextAndUrl($value, $url)->toString();
  }

}
