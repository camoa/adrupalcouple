<?php

namespace Drupal\geolocation_address\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\geolocation\GeocoderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddressWidgetController.
 *
 * @package Drupal\geolocation_address\Controller
 */
class GeocoderController extends ControllerBase {

  /**
   * Geocoder Manager.
   *
   * @var \Drupal\geolocation\GeocoderManager
   */
  protected GeocoderManager $geocoderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * Geocoder Controller.
   *
   * @param \Drupal\geolocation\GeocoderManager $geocoder_manager
   *   Geocoder Manager.
   */
  public function __construct(GeocoderManager $geocoder_manager) {
    $this->geocoderManager = $geocoder_manager;
  }

  /**
   * Return coordinates.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Geocoded coordinates.
   */
  public function geocode(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['geocoder'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (empty($data['address'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    $geocoder = $this->geocoderManager->getGeocoder($data['geocoder'], $data['geocoder_settings'] ?? []);
    if (is_string($data['address'])) {
      $geocoded_result = $geocoder->geocode($data['address']);
    }
    elseif (is_array($data['address'])) {
      /** @var \Drupal\address\Repository\AddressFormatRepository $addressFormatRepository */
      $addressFormatRepository = \Drupal::service('address.address_format_repository');
      $address_format = $addressFormatRepository->get($data['address']['countryCode']);
      if ($address_format) {
        $components = [
          '%givenName' => '',
          '%familyName' => '',
          '%organization' => '',
          '%addressLine1' => '',
          '%addressLine2' => '',
          '%locality' => '',
          '%administrativeArea' => '',
          '%postalCode' => '',
        ];
        foreach ($data['address'] as $component => $value) {
          if (array_key_exists('%' . $component, $components)) {
            $components['%' . $component] = $value;
          }
        }
        $address_string = trim(strtr($address_format->getFormat(), $components));
        $address_string = str_replace("\n\n", "\n", $address_string);
        $address_string = str_replace("\n", ", ", $address_string);
        $address_string .= ", " . ($data['address']['country'] ?? $data['address']['countryCode']);
      }
      else {
        $address_string = implode(', ', $data['address']);
      }

      $geocoded_result = $geocoder->geocode($address_string);
    }
    else {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (!isset($geocoded_result['location'])) {
      return new JsonResponse([], Response::HTTP_NOT_FOUND);
    }
    return new JsonResponse($geocoded_result['location']);
  }

  /**
   * Return formatted address data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current Request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Formatted address.
   */
  public function reverse(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['geocoder'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    if (empty($data['geocoder_settings'])) {
      return new JsonResponse([], Response::HTTP_BAD_REQUEST);
    }

    $geocoder = $this->geocoderManager->getGeocoder($data['geocoder'], $data['geocoder_settings']);

    if (!$data['latitude'] || !$data['longitude']) {
      return new JsonResponse(FALSE);
    }

    $address = $geocoder->reverseGeocode($data['latitude'], $data['longitude']);
    if (empty($address['elements']['countryCode'])) {
      return new JsonResponse(FALSE);
    }

    return new JsonResponse($address['elements']);
  }

}
