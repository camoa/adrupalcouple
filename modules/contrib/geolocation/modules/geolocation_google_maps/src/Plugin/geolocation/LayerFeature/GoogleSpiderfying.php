<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides Spiderfying function.
 *
 * @LayerFeature(
 *   id = "spiderfying",
 *   name = @Translation("Spiderfying"),
 *   description = @Translation("Split up overlapping markers on click."),
 *   type = "google_maps",
 * )
 */
class GoogleSpiderfying extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  protected array $scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/OverlappingMarkerSpiderfier/1.0.3/oms.min.js',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'spiderfiable_marker_path' => base_path() . \Drupal::service('extension.list.module')->getPath('geolocation_google_maps') . '/images/marker-plus.svg',
      'markersWontMove' => TRUE,
      'markersWontHide' => FALSE,
      'keepSpiderfied' => TRUE,
      'ignoreMapClick' => FALSE,
      'nearbyDistance' => 20,
      'circleSpiralSwitchover' => 9,
      'circleFootSeparation' => 23,
      'spiralFootSeparation' => 26,
      'spiralLengthStart' => 11,
      'spiralLengthFactor' => 4,
      'legWeight' => 1.5,
      'spiralIconWidth' => 23,
      'spiralIconHeight' => 32,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['spiderfiable_marker_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Marker Path'),
      '#description' => $this->t('Set relative or absolute path to the image to be displayed while markers are spiderfiable. Tokens supported.'),
      '#default_value' => $settings['spiderfiable_marker_path'],
    ];

    $form['markersWontMove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Markers won't move"),
      '#description' => $this->t('If you know that you won’t be moving any of the markers you add to this instance, you can save memory by setting this to true.'),
      '#default_value' => $settings['markersWontMove'],
    ];

    $form['markersWontHide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Markers won't hide"),
      '#description' => $this->t('If you know that you won’t be hiding any of the markers you add to this instance, you can save memory by setting this to true.'),
      '#default_value' => $settings['markersWontHide'],
    ];

    $form['keepSpiderfied'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep spiderfied'),
      '#description' => $this->t('By default, the OverlappingMarkerSpiderfier works like Google Earth, in that when you click a spiderfied marker, the markers unspiderfy before any other action takes place. Setting this to true overrides this behavior.'),
      '#default_value' => $settings['keepSpiderfied'],
    ];

    $form['ignoreMapClick'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore map click'),
      '#description' => $this->t('By default, clicking an empty spot on the map causes spiderfied markers to unspiderfy. Setting this option to true suppresses that behavior.'),
      '#default_value' => $settings['ignoreMapClick'],
    ];

    $form['nearbyDistance'] = [
      '#type' => 'number',
      '#title' => $this->t('Nearby distance'),
      '#description' => $this->t('This is the pixel radius within which a marker is considered to be overlapping a clicked marker.'),
      '#default_value' => $settings['nearbyDistance'],
    ];

    $form['circleSpiralSwitchover'] = [
      '#type' => 'number',
      '#title' => $this->t('Circle spiral switchover'),
      '#description' => $this->t('This is the lowest number of markers that will be fanned out into a spiral instead of a circle.'),
      '#default_value' => $settings['circleSpiralSwitchover'],
    ];

    $form['circleFootSeparation'] = [
      '#type' => 'number',
      '#title' => $this->t('Circle foot separation'),
      '#description' => $this->t('Determines the positioning of markers when spiderfied out into a circle.'),
      '#default_value' => $settings['circleFootSeparation'],
    ];

    $form['spiralFootSeparation'] = [
      '#type' => 'number',
      '#title' => $this->t('Spiral Foot Separation'),
      '#description' => $this->t('Determines the positioning of markers when spiderfied out into a spiral.'),
      '#default_value' => $settings['spiralFootSeparation'],
    ];

    $form['spiralLengthStart'] = [
      '#type' => 'number',
      '#title' => $this->t('Spiral length start'),
      '#default_value' => $settings['spiralLengthStart'],
    ];

    $form['spiralLengthFactor'] = [
      '#type' => 'number',
      '#title' => $this->t('Spiral length factor'),
      '#default_value' => $settings['spiralLengthFactor'],
    ];

    $form['legWeight'] = [
      '#type' => 'number',
      '#step' => '.1',
      '#title' => $this->t('Leg weight'),
      '#description' => $this->t('This determines the thickness of the lines joining spiderfied markers to their original locations.'),
      '#default_value' => $settings['legWeight'],
    ];

    $form['spiralIconWidth'] = [
      '#type' => 'number',
      '#title' => $this->t('Spiral Icon width'),
      '#description' => $this->t('Determines the width in Pixel of the marker'),
      '#default_value' => $settings['spiralIconWidth'],
    ];

    $form['spiralIconHeight'] = [
      '#type' => 'number',
      '#title' => $this->t('Spiral Icon height'),
      '#description' => $this->t('Determines the height in Pixel of the marker'),
      '#default_value' => $settings['spiralIconHeight'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    if (!empty($feature_settings['spiderfiable_marker_path'])) {
      $path = $this->token->replace($feature_settings['spiderfiable_marker_path'], $context);
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$render_array['#id']]['dataLayers'][$layer_id]['features'][$this->getPluginId()]['spiderfiable_marker_path'] = $path;
    }

    return $render_array;
  }

}
