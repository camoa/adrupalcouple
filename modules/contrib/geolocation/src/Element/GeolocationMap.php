<?php

namespace Drupal\geolocation\Element;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Template\Attribute;
use Drupal\geolocation\MapProviderManager;

/**
 * Provides a render element to display a geolocation map.
 *
 * Usage example:
 * @code
 * $form['map'] = [
 *   '#type' => 'geolocation_map',
 *   '#prefix' => $this->t('Geolocation Map Render Element'),
 *   '#description' => $this->t('Render element type "geolocation_map"'),
 *   '#maptype' => 'leaflet,
 *   '#centre' => [],
 *   '#id' => 'thisisanid',
 * ];
 * @endcode
 *
 * @RenderElement("geolocation_map")
 */
class GeolocationMap extends RenderElementBase {

  /**
   * Map Provider.
   *
   * @var \Drupal\geolocation\MapProviderManager
   */
  protected MapProviderManager $mapProviderManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mapProviderManager = \Drupal::service('plugin.manager.geolocation.mapprovider');
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
        [$this, 'preRenderMap'],
      ],
      '#maptype' => NULL,
      '#centre' => NULL,
      '#id' => NULL,
      '#controls' => NULL,
      '#layers' => [],
      '#context' => [],
    ];
  }

  /**
   * Map element.
   *
   * @param array $render_array
   *   Element.
   *
   * @return array
   *   Renderable map.
   */
  public function preRenderMap(array $render_array): array {
    $render_array['#theme'] = 'geolocation_map_wrapper';

    $render_array['#cache'] = array_merge_recursive(
      $render_array['#cache'] ?? [],
      ['contexts' => ['languages:language_interface']]
    );

    if (empty($render_array['#id'])) {
      $render_array['#id'] = uniqid('map-');
    }

    if (!empty($render_array['#controls'])) {
      uasort($render_array['#controls'], [
        SortArray::class,
        'sortByWeightProperty',
      ]);
    }

    if (!empty($render_array['#children'])) {
      uasort($render_array['#children'], [
        SortArray::class,
        'sortByWeightProperty',
      ]);
    }

    if (empty($render_array['#maptype'])) {
      if (\Drupal::moduleHandler()->moduleExists('geolocation_google_maps')) {
        $render_array['#maptype'] = 'google_maps';
      }
      elseif (\Drupal::moduleHandler()->moduleExists('geolocation_leaflet')) {
        $render_array['#maptype'] = 'leaflet';
      }
    }

    $map_provider = $this->mapProviderManager->getMapProvider($render_array['#maptype']);
    if (empty($map_provider)) {
      return $render_array;
    }

    $map_settings = [];
    if (
      !empty($render_array['#settings'])
      && is_array($render_array['#settings'])
    ) {
      $map_settings = $render_array['#settings'];
    }

    $map_settings = $map_provider->getSettings($map_settings);

    $render_array = BubbleableMetadata::mergeAttachments(
      [
        '#attached' => [
          'library' => [
            'geolocation/geolocation.map',
          ],
        ],
      ],
      $render_array
    );

    $render_array['#attributes'] = new Attribute($render_array['#attributes'] ?? []);
    $render_array['#attributes']->addClass('geolocation-map-wrapper');
    $render_array['#attributes']->setAttribute('id', $render_array['#id']);
    $render_array['#attributes']->setAttribute('data-map-type', $render_array['#maptype']);

    if (
      !empty($render_array['#centre']['lat'])
      && !empty($render_array['#centre']['lng'])
    ) {
      $render_array['#attributes']->setAttribute('data-lat', $render_array['#centre']['lat']);
      $render_array['#attributes']->setAttribute('data-lng', $render_array['#centre']['lng']);
    }

    if (
      !empty($render_array['#centre']['lat_north_east'])
      && !empty($render_array['#centre']['lng_north_east'])
      && !empty($render_array['#centre']['lat_south_west'])
      && !empty($render_array['#centre']['lng_south_west'])
    ) {
      $render_array['#attributes']->setAttribute('data-centre-lat-north-east', $render_array['#centre']['lat_north_east']);
      $render_array['#attributes']->setAttribute('data-centre-lng-north-east', $render_array['#centre']['lng_north_east']);
      $render_array['#attributes']->setAttribute('data-centre-lat-south-west', $render_array['#centre']['lat_south_west']);
      $render_array['#attributes']->setAttribute('data-centre-lng-south-west', $render_array['#centre']['lng_south_west']);
    }

    $context = [];
    if (!empty($render_array['#context'])) {
      $context = $render_array['#context'];
    }

    $render_array = $map_provider->alterRenderArray($render_array, $map_settings, $context);

    if (is_array($render_array['#layers'] ?? FALSE)) {
      foreach (Element::children($render_array['#layers']) as $layer_id) {
        $render_array['#children'][$layer_id] = $render_array['#layers'][$layer_id];
        unset($render_array['#layers'][$layer_id]);
      }
    }

    foreach (Element::children($render_array) as $child) {
      $render_array['#children'][$child] = $render_array[$child];
      unset($render_array[$child]);
    }

    return $render_array;
  }

  /**
   * Recursively return all locations in render array.
   *
   * @param array $render_array
   *   Geolocation Map render array.
   *
   * @return array
   *   Geolocation Map Locations.
   */
  public static function getLocations(array $render_array): array {
    $locations = [];
    if (
      !empty($render_array['#type'])
      && $render_array['#type'] == 'geolocation_map_location'
    ) {
      $locations[] = $render_array;
    }
    elseif (!empty($render_array['#children'])) {
      foreach ($render_array['#children'] as $child) {
        if (is_array($child)) {
          $locations = array_merge($locations, static::getLocations($child));
        }
      }
    }
    else {
      foreach (Element::children($render_array) as $key) {
        if (is_array($render_array[$key])) {
          $locations = array_merge($locations, static::getLocations($render_array[$key]));
        }
      }
    }

    return $locations;
  }

}
