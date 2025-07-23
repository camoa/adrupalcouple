<?php

declare(strict_types=1);

namespace Drupal\config_overlay;

use Drupal\config_overlay\EventSubscriber\ConfigTransformSubscriber;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Site\Settings;

/**
 * Modifies services on behalf of Config Overlay.
 */
class ConfigOverlayServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $settings = $container->get(Settings::class);
    $moduleList = $container->getParameter('container.modules');

    $legacyExportPrioritiesFromSettings = $settings->get('config_overlay_export_priorities');
    $exportPrioritiesFromSettings = $settings->get('config_overlay_priorities');
    if ($legacyExportPrioritiesFromSettings) {
      @trigger_error('Setting $settings[\'config_overlay_export_priorities\'] in settings.php is deprecated in config_overlay:2.4.1 and will be removed in config_overlay:3.0.0. Use $settings[\'config_overlay_priorities\'] instead. See https://www.drupal.org/node/3536832', E_USER_DEPRECATED);
      ConfigTransformSubscriber::setExportPriorities($legacyExportPrioritiesFromSettings);
    }
    elseif ($exportPrioritiesFromSettings) {
      ConfigTransformSubscriber::setExportPriorities($exportPrioritiesFromSettings);
    }
    elseif (isset($moduleList['config_split'])) {
      // If Config Split is installed, additionally run early on export and
      // late on import (so that in total Config Overlay runs both early and
      // late on both import and export). Running early on export makes sure
      // that stack-able configuration splits do not split off shipped
      // configuration. (Conversely, running late on import overlays that
      // shipped configuration for stack-able configuration splits.) Running
      // the transformation a second time also allows overlaying shipped
      // configuration of modules that are split off by a configuration split.
      // Note that if those modules themselves ship configuration splits the
      // shipped configuration of modules split of by this "nested" split will
      // _not_ be detected. To enable this, add another transformation
      // priority by setting $settings['config_overlay_export_priorities'] in
      // settings.php to "[-50, 50, 60]", for example.
      ConfigTransformSubscriber::setExportPriorities([-50, 50]);
    }
  }

}
