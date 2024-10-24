<?php

namespace Drupal\geolocation;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for geolocation geocoder plugins.
 */
interface GeocoderInterface extends PluginInspectionInterface {

  /**
   * Return additional options form.
   *
   * @return array
   *   Options form.
   */
  public function getOptionsForm(): array;

  /**
   * Return settings.
   *
   * @return array
   *   Settings.
   */
  public function getSettings(): array;

  /**
   * Process the form built above.
   *
   * @param array $form_element
   *   Options form.
   *
   * @return array|null
   *   Settings to store or NULL.
   */
  public function processOptionsForm(array $form_element): ?array;

  /**
   * Attach geocoding logic to input element.
   *
   * @param array $render_array
   *   Form containing the input element.
   * @param string $identifier
   *   Name of the input element.
   *
   * @return array|null
   *   Updated form element or NULL.
   */
  public function alterRenderArray(array &$render_array, string $identifier): ?array;

  /**
   * Geocode an address.
   *
   * @param string $address
   *   Address to geocode.
   *
   * @return array|null
   *   Location or NULL.
   */
  public function geocode(string $address): ?array;

  /**
   * Reverse geocode an address.
   *
   * Intended return subject to available data:
   *
   * @code
   * [
   *   'organization' => '',
   *   'address_line1' => '',
   *   'address_line2' => '',
   *   'postal_code' => '',
   *   'sorting_code' => '',
   *   'dependent_locality' => [],
   *   'locality' => [],
   *   'administrative_area' => [],
   *   'country' => [],
   *
   *   'formatted_address' => '',
   * ]
   * @endcode
   *
   * @param float $latitude
   *   Latitude to reverse geocode.
   * @param float $longitude
   *   Longitude to reverse geocode.
   *
   * @return array|null
   *   Address or NULL.
   */
  public function reverseGeocode(float $latitude, float $longitude): ?array;

}
