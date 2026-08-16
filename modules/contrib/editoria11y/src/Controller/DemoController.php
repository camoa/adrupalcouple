<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Editoria11y module.
 */
final class DemoController extends ControllerBase {

  /**
   * Page: summary demo with three panels.
   *
   * @return array
   *   A simple renderable array.
   */
  public function demo(): array {
    // @phpstan-ignore-next-line
    if (!\Drupal::service('module_handler')->moduleExists('editoria11y_csa')) {
      // @phpstan-ignore-next-line
      \Drupal::service('messenger')->addWarning('Activate the Editoria11y_CSA submodule to see developer tests.');
    }
    return [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'editoria11y/editoria11y',
          'editoria11y/editoria11y-demo',
        ],
      ],
      '#attributes' => [
        'id' => 'ed11y-demo',
      ],
    ];
  }

}
