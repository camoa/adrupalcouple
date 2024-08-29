<?php

namespace Drupal\Tests\geolocation_yandex\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of geolocation fields.
 *
 * @group geolocation
 */
class YandexGeocodingTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'geolocation',
    'geolocation_yandex',
    'geolocation_yandex_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test Latitude.
   *
   * @var float
   */
  protected float $bishkekFlooredLatitude = 42.86;

  /**
   * Test Longitude.
   *
   * @var float
   */
  protected float $bishkekFlooredLongitude = 74.56;

  /**
   * Test geocoding.
   */
  public function testGeocoder(): void {
    /** @var \Drupal\geolocation\GeocoderInterface $geocoder */
    $geocoder = \Drupal::service('plugin.manager.geolocation.geocoder')->getGeocoder('yandex');
    $location = $geocoder->geocode('Бишкек, Кыргызстан');
    $this->assertArrayHasKey('location', $location);

    $this->assertEquals(round($this->bishkekFlooredLatitude, 1), round($location['location']['lat'], 1));
    $this->assertEquals(round($this->bishkekFlooredLongitude, 1), round($location['location']['lng'], 1));
  }

  /**
   * Test reverse geocoding.
   */
  public function testReverseGeocoder(): void {
    /** @var \Drupal\geolocation\GeocoderInterface $geocoder */
    $geocoder = \Drupal::service('plugin.manager.geolocation.geocoder')->getGeocoder('yandex');
    $address = $geocoder->reverseGeocode($this->bishkekFlooredLatitude, $this->bishkekFlooredLongitude);
    $this->assertArrayHasKey('atomics', $address);
    $this->assertEquals('kg', $address['atomics']['countryCode']);
  }

}
