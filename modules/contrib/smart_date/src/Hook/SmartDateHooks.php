<?php

declare(strict_types=1);

namespace Drupal\smart_date\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for smart_date.
 */
class SmartDateHooks {

  /**
   * Implements hook_config_schema_info_alter().
   */
  #[Hook('config_schema_info_alter')]
  public function configSchemaInfoAlter(array &$definitions): void {
    if (isset($definitions['views.filter_value.date'])) {
      $definitions['views.filter_value.date']['mapping']['granularity'] = [
        'type' => 'string',
        'label' => 'Granularity',
      ];
    }
    if (isset($definitions['views.argument.date'])) {
      $definitions['views.argument.date']['mapping']['date_token'] = [
        'type' => 'string',
        'label' => 'Date Token',
      ];
      $definitions['views.argument.date']['mapping']['format'] = [
        'type' => 'string',
        'label' => 'Format String',
      ];
      $definitions['views.argument.date']['mapping']['granularity'] = [
        'type' => 'string',
        'label' => 'Granularity',
      ];
    }
  }

}
