<?php

namespace Drupal\geolocation_bing\Plugin\geolocation\MapProvider;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\MapProviderBase;

/**
 * Provides Bing Maps API.
 *
 * @MapProvider(
 *   id = "bing",
 *   name = @Translation("Bing Maps (Deprecated)"),
 *   description = @Translation("Bing support. Replaced by Azure Maps."),
 * )
 */
class Bing extends MapProviderBase {

  /**
   * Bing API Url.
   *
   * @var string
   */
  public static string $apiBaseUrl = 'https://www.bing.com/api/maps/mapcontrol';

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return array_replace_recursive(
      parent::getDefaultSettings(),
      [
        'zoom' => 10,
        'height' => '400px',
        'width' => '100%',
        'show_dashboard' => FALSE,
        'show_locate_me_button' => FALSE,
        'show_map_type_selector' => FALSE,
        'show_traffic_button' => FALSE,
        'show_terms_link' => FALSE,
        'show_zoom_buttons' => FALSE,
        'show_scalebar' => FALSE,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings): array {
    $settings = parent::getSettings($settings);

    $settings['zoom'] = (int) $settings['zoom'];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $settings): array {
    $summary = parent::getSettingsSummary($settings);
    $summary[] = $this->t('Zoom level: @zoom', ['@zoom' => $settings['zoom']]);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['height']]);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['width']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], array $context = []): array {
    $settings += self::getDefaultSettings();
    if ($parents) {
      $parents_string = implode('][', $parents);
    }
    else {
      $parents_string = NULL;
    }

    $form = parent::getSettingsForm($settings, $parents, $context);

    $form['height'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['height'],
    ];
    $form['width'] = [
      '#group' => $parents_string,
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('Enter the dimensions and the measurement units. E.g. 200px or 100%.'),
      '#size' => 4,
      '#default_value' => $settings['width'],
    ];
    $form['zoom'] = [
      '#group' => $parents_string,
      '#type' => 'select',
      '#title' => $this->t('Zoom level'),
      '#options' => range(0, 20),
      '#description' => $this->t('The initial resolution at which to display the map, where zoom 0 corresponds to a map of the Earth fully zoomed out, and higher zoom levels zoom in at a higher resolution.'),
      '#default_value' => $settings['zoom'],
      '#process' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'processGroup'],
        ['\Drupal\Core\Render\Element\Select', 'processSelect'],
      ],
      '#pre_render' => [
        ['\Drupal\Core\Render\Element\RenderElementBase', 'preRenderGroup'],
      ],
    ];
    $form['show_breadcrumb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Breadcrumb'),
      '#default_value' => $settings['show_breadcrumb'],
    ];
    $form['show_dashboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Dashboard'),
      '#default_value' => $settings['show_dashboard'],
    ];
    $form['show_locate_me_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show LocateMe button'),
      '#default_value' => $settings['show_locate_me_button'],
    ];
    $form['show_map_type_selector'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show MapType selector'),
      '#default_value' => $settings['show_map_type_selector'],
    ];
    $form['show_traffic_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Traffic button'),
      '#default_value' => $settings['show_traffic_button'],
    ];
    $form['show_terms_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Terms link'),
      '#default_value' => $settings['show_terms_link'],
    ];
    $form['show_zoom_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Zoom buttons'),
      '#default_value' => $settings['show_zoom_buttons'],
    ];
    $form['show_scalebar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Scale bar'),
      '#default_value' => $settings['show_scalebar'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function getControlPositions(): array {
    return [
      'GEOLOCATION_BING_TOP_LEFT' => t('Top left'),
      'GEOLOCATION_BING_TOP_RIGHT' => t('Top right'),
      'GEOLOCATION_BING_BOTTOM_LEFT' => t('Bottom left'),
      'GEOLOCATION_BING_BOTTOM_RIGHT' => t('Bottom right'),
    ];
  }

  /**
   * Get Bing API Base URL.
   *
   * @return string
   *   Base Url.
   */
  public function getApiUrl(): string {
    $config = \Drupal::config('bing_maps.settings');

    $api_key = $config->get('key');

    return self::$apiBaseUrl . '?key=' . $api_key . '&callback=' . "DrupalGeolocationBingLoader";
  }

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array $render_array, array $map_settings = [], array $context = []): array {
    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                'scripts' => [$this->getApiUrl()],
              ],
            ],
          ],
        ],
        'library' => [
          'geolocation_bing/geolocation_bing.loader',
        ],
      ]
    );

    return parent::alterRenderArray($render_array, $map_settings, $context);
  }

}
