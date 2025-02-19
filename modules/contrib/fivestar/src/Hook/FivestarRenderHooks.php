<?php

declare(strict_types=1);

namespace Drupal\fivestar\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations used to render debugging information.
 */
final class FivestarRenderHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'fivestar_static' => [
        'variables' => [
          'rating' => NULL,
          'stars' => 5,
          'vote_type' => 'vote',
          'widget' => ['name' => 'default', 'css' => ''],
        ],
      ],
      'fivestar_static_element' => [
        'variables' => [
          'star_display' => NULL,
          'title' => NULL,
          'description' => NULL,
        ],
      ],
      'fivestar_summary' => [
        'variables' => [
          'user_rating' => NULL,
          'average_rating' => NULL,
          'votes' => 0,
          'stars' => 5,
        ],
      ],
      // This is dead code in Drupal 10.
      'fivestar_preview' => [
        'function' => 'theme_fivestar_preview',
        'variables' => [
          'style' => NULL,
          'text' => NULL,
          'stars' => NULL,
          'unvote' => NULL,
          'title' => NULL,
        ],
        'file' => 'includes/fivestar.theme.inc',
      ],
      'fivestar_formatter_rating' => [
        'render element' => 'element',
      ],
      'fivestar_formatter_percentage' => [
        'render element' => 'element',
      ],
    ];
  }

}
