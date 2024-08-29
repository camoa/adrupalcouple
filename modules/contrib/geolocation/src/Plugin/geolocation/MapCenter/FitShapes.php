<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\MapCenterBase;
use Drupal\geolocation\MapCenterInterface;

/**
 * Fit shapes.
 *
 * @MapCenter(
 *   id = "fit_shapes",
 *   name = @Translation("Fit shapes"),
 *   description = @Translation("Automatically fit map to displayed shapes."),
 * )
 */
class FitShapes extends MapCenterBase implements MapCenterInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'min_zoom' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(string $option_id = NULL, array $settings = [], array $context = []): array {
    $form = parent::getSettingsForm($option_id, $settings, $context);
    $form['min_zoom'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Set a minimum zoom, especially useful when only location is centered on.'),
      '#default_value' => $settings['min_zoom'],
    ];

    return $form;
  }

}
