<?php

namespace Drupal\geolocation_google_places_api\Plugin\geolocation\Geocoder;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Error;
use Drupal\geolocation_google_maps\GoogleGeocoderBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides the Google Places API.
 *
 * @Geocoder(
 *   id = "google_places_api",
 *   name = @Translation("Google Places API"),
 *   description = @Translation("Attention: This Plugin needs you to follow Google Places API TOS and either use the Attribution Block or provide it yourself."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 *   frontendCapable = true,
 *   reverseCapable = false,
 * )
 */
class GooglePlacesAPI extends GoogleGeocoderBase {

  /**
   * {@inheritdoc}
   */
  public function alterRenderArray(array &$render_array, string $identifier): ?array {
    $render_array = parent::alterRenderArray($render_array, $identifier);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      $render_array['#attached'] ?? [],
      [
        'library' => [
          'geolocation_google_places_api/geolocation_google_places_api.googleplacesicons',
        ],
      ]
    );

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($address): ?array {

    if (empty($address)) {
      return NULL;
    }

    $config = \Drupal::config('geolocation_google_maps.settings');

    $request_url = $this->googleMapsService->getGoogleMapsApiUrl() . '/maps/api/place/autocomplete/json?input=' . $address;

    if (!empty($this->configuration['component_restrictions']['country'])) {
      $request_url .= '&components=country:' . $this->configuration['component_restrictions']['country'];
    }
    if (!empty($config->get('google_map_custom_url_parameters')['language'])) {
      $request_url .= '&language=' . $config->get('google_map_custom_url_parameters')['language'];
    }

    try {
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['predictions'][0]['place_id'])
    ) {
      return NULL;
    }

    try {
      $details_url = $this->googleMapsService->getGoogleMapsApiUrl() . '/maps/api/place/details/json?placeid=' . $result['predictions'][0]['place_id'];
      $details = Json::decode(\Drupal::httpClient()->request('GET', $details_url)->getBody());

    }
    catch (RequestException $e) {
      $logger = \Drupal::logger('geolocation');
      Error::logException($logger, $e);
      return NULL;
    }

    if (
      $details['status'] != 'OK'
      || empty($details['result']['geometry']['location'])
    ) {
      return NULL;
    }

    return [
      'location' => [
        'lat' => $details['result']['geometry']['location']['lat'],
        'lng' => $details['result']['geometry']['location']['lng'],
      ],
      // @todo Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] + 0.005 : $details['result']['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lat'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($details['result']['geometry']['viewport']) ? $details['result']['geometry']['location']['lng'] - 0.005 : $details['result']['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($details['result']['formatted_address']) ? '' : $details['result']['formatted_address'],
    ];
  }

}
