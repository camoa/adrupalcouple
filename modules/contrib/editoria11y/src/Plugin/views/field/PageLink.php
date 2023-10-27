<?php

namespace Drupal\editoria11y\Plugin\views\field;

use Drupal\Core\Link;
use Drupal\Core\Url;
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

    if (!empty($value)) {

      if (isset($values->editoria11y_results_page_path)) {
        $path = $values->editoria11y_results_page_path;
      }
      else {
        $path = $values->editoria11y_dismissals_page_path;
      }

      $url = Url::fromUserInput($path, [
        'query' => [
          'ed1ref' => $path
        ]
      ]);

      $value = Link::fromTextAndUrl($value, $url)->toString();

    }

    return $value;
  }

}
