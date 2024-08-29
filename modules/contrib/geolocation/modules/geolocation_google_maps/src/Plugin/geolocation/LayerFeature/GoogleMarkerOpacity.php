<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Google Maps.
 *
 * @LayerFeature(
 *   id = "marker_opacity",
 *   name = @Translation("Marker Opacity"),
 *   description = @Translation("Opacity properties."),
 *   type = "google_maps",
 * )
 */
class GoogleMarkerOpacity extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'opacity' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['opacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Opacity'),
      '#step' => 0.01,
      '#min' => 0,
      '#max' => 1,
      '#description' => $this->t('1 = solid, 0 = invisible'),
      '#default_value' => $settings['opacity'],
    ];

    return $form;
  }

}
