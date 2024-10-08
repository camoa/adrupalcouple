<?php

namespace Drupal\geolocation_dummy_geocoder\Plugin\geolocation\Geocoder;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation\GeocoderBase;

/**
 * Provides the Dummy Geocoder.
 *
 * @Geocoder(
 *   id = "dummy",
 *   name = @Translation("Dummy Geocoder"),
 *   locationCapable = true,
 *   boundaryCapable = false,
 * )
 */
class Dummy extends GeocoderBase {

  /**
   * Test targets.
   *
   * @var array
   */
  public static array $targets = [
    'Berlin' => [
      'lat' => 52.517037,
      'lng' => 13.38886,
    ],
    'Vladivostok' => [
      'lat' => 43.115284,
      'lng' => 131.885401,
    ],
    'Santiago de Chile' => [
      'lat' => -33.437913,
      'lng' => -70.650456,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array &$render_array, $identifier): ?array {
    $render_array['geolocation_geocoder_dummy'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Enter one of the statically defined targets. See geolocation/Geocoder/Dummy.php.'),
      '#attributes' => [
        'class' => [
          'form-autocomplete',
          'geolocation-geocoder-dummy',
        ],
        'data-source-identifier' => $identifier,
      ],
    ];

    $render_array = BubbleableMetadata::mergeAttachments($render_array, [
      '#attached' => [
        'library' => [
          0 => 'geolocation_dummy_geocoder/geocoder',
        ],
      ],
    ]);

    $render_array['geolocation_geocoder_dummy_state'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
      '#attributes' => [
        'class' => [
          'geolocation-geocoder-dummy-state',
        ],
        'data-source-identifier' => $identifier,
      ],
    ];

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address): ?array {

    if (empty($address)) {
      return NULL;
    }

    if (!empty(self::$targets[$address])) {
      return [
        'location' => [
          'lat' => self::$targets[$address]['lat'],
          'lng' => self::$targets[$address]['lng'],
        ],
        'boundary' => [
          'lat_north_east' => self::$targets[$address]['lat'] + 0.01,
          'lng_north_east' => self::$targets[$address]['lng'] + 0.01,
          'lat_south_west' => self::$targets[$address]['lat'] + 0.01,
          'lng_south_west' => self::$targets[$address]['lng'] + 0.01,
        ],
      ];
    }
    else {
      return NULL;
    }
  }

}
