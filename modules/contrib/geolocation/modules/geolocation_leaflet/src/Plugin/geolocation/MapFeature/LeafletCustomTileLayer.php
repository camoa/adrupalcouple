<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides map tile layer support.
 *
 * @MapFeature(
 *   id = "leaflet_custom_tile_layer",
 *   name = @Translation("Tile Layer - Custom"),
 *   description = @Translation("Set a custom map tile layer."),
 *   type = "leaflet",
 * )
 */
class LeafletCustomTileLayer extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return array_replace_recursive(
      parent::getDefaultSettings(),
      [
        'tile_layer_url' => '//{s}.tile.osm.org/{z}/{x}/{y}.png',
        'tile_layer_attribution' => '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
        'tile_layer_subdomains' => 'abc',
        'tile_layer_zoom' => 18,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['tile_layer_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Enter a tile server url like "https://{s}.tile.osm.org/{z}/{x}/{y}.png".'),
      '#default_value' => $settings['tile_layer_url'],
    ];
    $form['tile_layer_attribution'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Attribution'),
      '#description' => $this->t(
        'Enter the tile server attribution like %attr.',
        ['%attr' => htmlspecialchars('&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors')]
      ),
      '#default_value' => $settings['tile_layer_attribution'],
    ];
    $form['tile_layer_subdomains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subdomains'),
      '#description' => $this->t('Enter the tile server subdomains like "abc".'),
      '#default_value' => $settings['tile_layer_subdomains'],
    ];
    $form['tile_layer_zoom'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Zoom'),
      '#description' => $this->t('Enter the tile server max zoom.'),
      '#default_value' => $settings['tile_layer_zoom'],
    ];

    return $form;
  }

}
