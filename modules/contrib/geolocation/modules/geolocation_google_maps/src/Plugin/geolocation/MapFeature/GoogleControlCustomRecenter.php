<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlCustomElementBase;

/**
 * Provides Recenter control element.
 *
 * @MapFeature(
 *   id = "control_recenter",
 *   name = @Translation("Map Control - Recenter"),
 *   description = @Translation("Add button to recenter map."),
 *   type = "google_maps",
 * )
 */
class GoogleControlCustomRecenter extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_recenter'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Recenter'),
      '#attributes' => [
        'class' => ['recenter'],
      ],
    ];

    return $render_array;
  }

}
