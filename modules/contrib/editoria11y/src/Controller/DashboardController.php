<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\editoria11y\Dashboard;
use Drupal\editoria11y\DashboardInterface;

/**
 * Provides route responses for the Editoria11y module.
 */
final class DashboardController extends ControllerBase {
  /**
   * Dashboard property.
   *
   * @var \Drupal\editoria11y\Dashboard
   */
  protected DashboardInterface|Dashboard $dashboard;

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\editoria11y\DashboardInterface $dashboard
   *   Dashboard property.
   */
  public function __construct(DashboardInterface $dashboard) {
    $this->dashboard = $dashboard;
  }

  /**
   * Creates a dashboard.
   *
   * @param \Drupal\editoria11y\DashboardInterface $container
   *   Interface.
   */
  public static function create($container) {
    return new self(
      $container->get('editoria11y.dashboard'),
    );
  }

  /**
   * Get a list of export links.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getExportLinks(): array {

    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    // @phpstan-ignore-next-line
    $route_provider = \Drupal::service('router.route_provider');

    $pages = $route_provider->getRoutesByNames(['view.ed11y_export.pages']);
    $dismissals = $route_provider->getRoutesByNames(['view.ed11y_export.dismissals']);
    $issues = $route_provider->getRoutesByNames(['view.ed11y_export.results']);

    $links = [];
    if (count($pages) === 1) {
      $links[] = Link::createFromRoute($this->t('Export pages with alerts'), 'view.ed11y_export.pages')->toString();
    }
    if (count($issues) === 1) {
      $links[] = Link::createFromRoute($this->t('Export alerts'), 'view.ed11y_export.results')->toString();
    }
    if (count($dismissals) === 1) {
      $links[] = Link::createFromRoute($this->t('Export dismissals'), 'view.ed11y_export.dismissals')->toString();
    }

    // Without the batch-export submodule, offer the module's built-in
    // streamed CSV exports of the dashboard Views instead.
    if (count($links) === 0) {
      $view_storage = $this->entityTypeManager()->getStorage('view');
      $streamed = [
        ['ed11y_result', 'pages_with_alerts', $this->t('Export pages with alerts')],
        ['ed11y_result', 'issues__page', $this->t('Export alerts')],
        ['ed11y_action', 'dismissals__page', $this->t('Export dismissals')],
      ];
      foreach ($streamed as [$view_id, $display_id, $label]) {
        $view = $view_storage->load($view_id);
        if (!$view) {
          continue;
        }
        $displays = $view->get('display');
        if (!isset($displays[$display_id])) {
          continue;
        }
        $links[] = Link::createFromRoute($label, 'editoria11y.csv_export', [
          'view_id' => $view_id,
          'display_id' => $display_id,
        ])->toString();
      }
    }

    if (count($links) === 0) {
      return [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'editoria11y-no-export-links',
          ],
        ],
        'child_element_1' => [
          '#type' => 'html_tag',
          '#tag' => 'em',
          '#value' => $this->t('Install the Editoria11y Export Batch Processor module to download these reports.'),
        ],
      ];

    }
    $return = [];
    $return[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Export results'),
    ];
    $list = [];
    foreach ($links as $link) {
      $list[] = $link;
    }
    $return[] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $list,
    ];
    return $return;

  }

  /**
   * Builds a render element for an embedded Views display.
   *
   * Replaces views_embed_view(), deprecated in Drupal 11.4. That function
   * returned NULL for a missing view, while the bare '#type' => 'view'
   * element throws a ViewRenderElementException during pre-render. The
   * module's Views are optional config that sites are free to delete or
   * edit, so the display is confirmed to exist before it is embedded.
   *
   * @param string $view_id
   *   The view config entity ID.
   * @param string $display_id
   *   The display ID within that view.
   *
   * @return array
   *   A renderable array; renders as nothing if the display is unavailable.
   */
  private function embedView(string $view_id, string $display_id): array {
    // Invalidate this page if the view is later deleted, restored or edited.
    $element = [
      '#cache' => [
        'tags' => ['config:views.view.' . $view_id],
      ],
    ];

    $view = $this->entityTypeManager()->getStorage('view')->load($view_id);
    if (!$view) {
      return $element;
    }
    $displays = $view->get('display');
    if (!isset($displays[$display_id])) {
      return $element;
    }

    return $element + [
      '#type' => 'view',
      '#name' => $view_id,
      '#display_id' => $display_id,
      '#arguments' => [],
    ];
  }

  /**
   * Page: summary dashboard with three panels.
   *
   * @return array
   *   A simple renderable array.
   */
  public function dashboard(): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['layout-container'],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-container', 'layout-row'],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['layout-column', 'layout-column--half', 'ed11y-results-view'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t('Top alerts', [], ['context' => 'problems']),
          ],
          'view' => $this->embedView('ed11y_result', 'block_top_issues'),
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['layout-column', 'layout-column--half', 'ed11y-pages-view'],
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $this->t('Pages with the most alerts', [], ['context' => 'problems']),
          ],
          'view' => $this->embedView('ed11y_result', 'block_most_issues'),
        ],
      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-container'],
        ],

        [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('Recent alerts', [], ['context' => 'problems']),
        ],
        'view' => $this->embedView('ed11y_result', 'block_recent_issues'),

      ],
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-container', 'ed11y-dismissals-view'],
        ],
        [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('Recent dismissals'),
        ],
        'view' => $this->embedView('ed11y_action', 'recent_dismissals'),
      ],
      [
        '#type' => 'container',
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['layout-container'],
          ],
          [
            $this->getExportLinks(),
          ],
        ],

      ],
    ];
  }

}
