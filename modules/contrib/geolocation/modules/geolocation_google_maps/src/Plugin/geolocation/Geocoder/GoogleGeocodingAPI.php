<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\Geocoder;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Utility\Error;
use Drupal\geolocation\KeyProvider;
use Drupal\geolocation_google_maps\GoogleGeocoderBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides the Google Geocoding API.
 *
 * @Geocoder(
 *   id = "google_geocoding_api",
 *   name = @Translation("Google Geocoding API"),
 *   description = @Translation("You do require an API key for this plugin to work."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 *   frontendCapable = true,
 *   reverseCapable = true,
 * )
 */
class GoogleGeocodingAPI extends GoogleGeocoderBase {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultSettings(): array {
    $config = \Drupal::config('geolocation_google_maps.settings');

    $default_settings = parent::getDefaultSettings();
    if (!empty($config->get('google_map_custom_url_parameters')['region'])) {
      $default_settings['region'] = $config->get('google_map_custom_url_parameters')['region'];
    }

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    $settings = parent::getSettings();

    $parameters = [];

    if (!empty($settings['region'])) {
      $parameters['region'] = $settings['region'];
    }

    $settings['google_api_url'] = $this->googleMapsService->getGoogleMapsApiUrl($parameters, '\js');

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    $settings = $this->getSettings();
    $form = parent::getOptionsForm();

    $form += [
      'region' => [
        '#type' => 'textfield',
        '#title' => $this->t('Region'),
        '#description' => $this->t('Make a region biasing by providing a ccTLD country code. See <a href="https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes">Region Biasing</a>'),
        '#default_value' => $settings['region'] ?? '',
        '#size' => 5,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode(string $address): ?array {
    if (empty($address)) {
      return NULL;
    }

    $config = \Drupal::config('geolocation_google_maps.settings');

    $query_params = [
      'address' => $address,
    ];

    if (!empty($config->get('google_map_api_server_key'))) {
      $query_params['key'] = KeyProvider::getKeyValue($config->get('google_map_api_server_key'));
    }
    elseif (!empty($config->get('google_map_api_key'))) {
      $query_params['key'] = KeyProvider::getKeyValue($config->get('google_map_api_key'));
    }

    if (!empty($this->configuration['component_restrictions'])) {
      $components = [];
      foreach ($this->configuration['component_restrictions'] as $component_id => $component_value) {
        $components[] = $component_id . ':' . $component_value;
      }
      $query_params['components'] = implode('|', $components);
    }
    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $query_params['language'] = $config->get('google_map_custom_url_parameters')['language'];
    }
    if (!empty($this->configuration['region'])) {
      $query_params['region'] = $this->configuration['region'];
    }
    if (
      !empty($this->configuration['boundary_restriction'])
      && !empty($this->configuration['boundary_restriction']['south'])
      && !empty($this->configuration['boundary_restriction']['west'])
      && !empty($this->configuration['boundary_restriction']['north'])
      && !empty($this->configuration['boundary_restriction']['east'])
    ) {
      $bounds = $this->configuration['boundary_restriction']['south'] . ',';
      $bounds .= $this->configuration['boundary_restriction']['west'] . '|';
      $bounds .= $this->configuration['boundary_restriction']['north'] . ',';
      $bounds .= $this->configuration['boundary_restriction']['east'];
      $query_params['bounds'] = $bounds;
    }

    try {
      $result = Json::decode(\Drupal::httpClient()
        ->get($this->googleMapsService->getGoogleMapsApiUrl($query_params, '/geocode/json'))
        ->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      if (isset($result['error_message'])) {
        \Drupal::logger('geolocation')->error(t('Unable to geocode "@address" with error: "@error". Request URL: @url', [
          '@address' => $address,
          '@error' => $result['error_message'],
        ]));
      }
      return NULL;
    }

    return [
      'location' => [
        'lat' => $result['results'][0]['geometry']['location']['lat'],
        'lng' => $result['results'][0]['geometry']['location']['lng'],
      ],
      'boundary' => [
        'lat_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
      'atomics' => $this->getAddressAtomics($result),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode(float $latitude, float $longitude): ?array {
    $config = \Drupal::config('geolocation_google_maps.settings');

    $query_params = [
      'latlng' => $latitude . ',' . $longitude,
    ];

    if (!empty($config->get('google_map_api_server_key'))) {
      $query_params['key'] = KeyProvider::getKeyValue($config->get('google_map_api_server_key'));
    }
    elseif (!empty($config->get('google_map_api_key'))) {
      $query_params['key'] = KeyProvider::getKeyValue($config->get('google_map_api_key'));
    }

    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $query_params['language'] = $config->get('google_map_custom_url_parameters')['language'];
    }

    try {
      $result = Json::decode(\Drupal::httpClient()
        ->get($this->googleMapsService->getGoogleMapsApiUrl($query_params, '/geocode/json'))
        ->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      if (isset($result['error_message'])) {
        \Drupal::logger('geolocation')->error(t('Unable to reverse geocode "@latitude, $longitude" with error: "@error". Request URL: @url', [
          '@latitude' => $latitude,
          '@$longitude' => $longitude,
          '@error' => $result['error_message'],
        ]));
      }
      return NULL;
    }

    if (empty($result['results'][0]['address_components'])) {
      return NULL;
    }

    $address_atomics = $this->getAddressAtomics($result);

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'string' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }

  /**
   * Gets array of geo data by Google Api result.
   *
   * @param array $result
   *   Google API result array.
   *
   * @return array
   *   Sorted array of geo data.
   */
  protected function getAddressAtomics(array $result): array {

    $addressAtomicsMapping = [
      'streetNumber' => [
        'type' => 'street_number',
      ],
      'route' => [
        'type' => 'route',
      ],
      'locality' => [
        'type' => 'locality',
      ],
      'county' => [
        'type' => 'administrative_area_level_2',
      ],
      'countyCode' => [
        'type' => 'administrative_area_level_2',
        'short' => TRUE,
      ],
      'postalCode' => [
        'type' => 'postal_code',
      ],
      'administrativeArea' => [
        'type' => 'administrative_area_level_1',
        'short' => TRUE,
      ],
      'administrativeAreaLong' => [
        'type' => 'administrative_area_level_1',
      ],
      'country' => [
        'type' => 'country',
      ],
      'countryCode' => [
        'type' => 'country',
        'short' => TRUE,
      ],
      'postalTown' => [
        'type' => 'postal_town',
      ],
      'neighborhood' => [
        'type' => 'neighborhood',
      ],
      'premise' => [
        'type' => 'premise',
      ],
      'political' => [
        'type' => 'political',
      ],
    ];

    $address_atomics = [];
    foreach ($result['results'][0]['address_components'] as $component) {
      foreach ($addressAtomicsMapping as $atomic => $google_format) {
        if ($google_format['type'] == $component['types'][0]) {
          if (!empty($google_format['short'])) {
            $address_atomics[$atomic] = $component['short_name'];
          }
          else {
            $address_atomics[$atomic] = $component['long_name'];
          }
        }
      }
    }

    return $address_atomics;
  }

}
