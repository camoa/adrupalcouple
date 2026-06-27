<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_translate\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Forces the admin theme on the untranslated_* worklist views.
 *
 * These views rely on Views Bulk Operations — the checkbox column and the
 * action bar only render in the admin theme. An editor who lacks the
 * "view the administration theme" permission otherwise gets the site's default
 * (front-end) theme for these admin pages, where the VBO checkboxes do not
 * render at all (the reported "no checkboxes"). Pin these three admin tools to
 * the configured admin theme so the bulk UI always works, for any user who can
 * reach them.
 */
final class UntranslatedAdminThemeNegotiator implements ThemeNegotiatorInterface {

  private const ROUTES = [
    'view.untranslated_content.page_1',
    'view.untranslated_terms.page_1',
    'view.untranslated_blocks.page_1',
  ];

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    return in_array($route_match->getRouteName(), self::ROUTES, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match): ?string {
    return $this->configFactory->get('system.theme')->get('admin') ?: 'claro';
  }

}
