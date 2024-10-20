<?php

namespace Drupal\Tests\geolocation_yandex\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Tests the Yandex JavaScript functionality.
 *
 * @group geolocation
 */
class GeolocationYandexJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'geolocation',
    'geolocation_yandex',
    'geolocation_yandex_test',
  ];

  /**
   * Map provider ID.
   *
   * @var string
   */
  protected string $mapProviderId = 'yandex';

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected MapProviderInterface $mapProvider;

  /**
   * Tests the Google Marker.
   */
  public function testMarker(): void {
    $this->drupalGet('geolocation-yandex-test-view');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container ymaps', 5000);
    $this->assertNotEmpty($result, "Yandex map present.");

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container ymaps[class="ymaps3x0--marker"]:last-child ymaps[class*="default-marker__icon-box"]', 5000);
    $this->assertNotEmpty($result, "Marker element present.");

    $this->assertSession()->elementNotExists('css', 'ymaps:not([class*="default-marker__hider"]) > [class*="default-marker__popup-container"]');

    $result->click();

    $this->assertSession()->elementExists('css', 'ymaps:not([class*="default-marker__hider"]) > [class*="default-marker__popup-container"]');
  }

}
