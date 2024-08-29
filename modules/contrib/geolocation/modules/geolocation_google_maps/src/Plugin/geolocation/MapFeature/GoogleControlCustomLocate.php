<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlCustomElementBase;

/**
 * Provides Locate control element.
 *
 * @MapFeature(
 *   id = "control_locate",
 *   name = @Translation("Map Control - Locate"),
 *   description = @Translation("Add button to center on client location. Hidden on non-https connection."),
 *   type = "google_maps",
 * )
 */
class GoogleControlCustomLocate extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $render_array['#controls'][$this->pluginId]['control_locate'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Locate'),
      '#attributes' => [
        'class' => ['locate'],
      ],
    ];

    return $render_array;
  }

}
