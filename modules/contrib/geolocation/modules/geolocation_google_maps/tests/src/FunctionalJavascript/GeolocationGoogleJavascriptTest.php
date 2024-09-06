<?php

namespace Drupal\Tests\geolocation_google_maps\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\geolocation\MapProviderInterface;
use Drupal\views\Entity\View;

/**
 * Tests the GoogleMaps JavaScript functionality.
 *
 * @group geolocation
 */
class GeolocationGoogleJavascriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'geolocation_google_js_errors',
    'geolocation',
    'geolocation_google_maps',
    'geolocation_google_maps_test',
    'geolocation_google_maps_demo',
  ];

  /**
   * Map provider ID.
   *
   * @var string
   */
  protected string $mapProviderId = 'google_maps';

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected MapProviderInterface $mapProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mapProvider = \Drupal::service('plugin.manager.geolocation.mapprovider')->getMapProvider($this->mapProviderId);

    $view = View::load('geolocation_demo_common_map');

    $display = &$view->getDisplay('default');

    $display['display_options']['pager']['type'] = 'none';
    $display['display_options']['pager']['options'] = [];

    $display['display_options']['style']['options']['map_provider_id'] = $this->mapProviderId;
    $display['display_options']['style']['options']['map_provider_settings'] = $this->mapProvider->getSettings([]);

    $display['display_options']['style']['options']['map_provider_settings']['zoom'] = '1';
    $display['display_options']['style']['options']['map_provider_settings']['map_features'] = [];

    $view->save();
  }

  /**
   * Tests the Google Marker.
   */
  public function testMarker(): void {
    $this->drupalGet('geolocation-demo/common-map');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $googleErrorMessage = $this->assertSession()->waitForElement('css', '.gm-err-message');
    if ($googleErrorMessage) {
      $errors = $this->getSession()->evaluateScript("sessionStorage.getItem('geolocation_google_js_errors')");
      var_dump("\n" . $errors . "\n");
    }
    $this->assertEmpty($googleErrorMessage, "No Google error messages");

    $result = $this->assertSession()->elementExists('css', '.field-content span[typeof="GeoCoordinates"]');
    $this->assertNotEmpty($result, "Location field content present.");
    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container img[src="https://maps.gstatic.com/mapfiles/transparent.png"]', 5000);
    // Fails randomly, so just try again.
    if (empty($result)) {
      $this->drupalGet('geolocation-demo/common-map');
      $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container img[src="https://maps.gstatic.com/mapfiles/transparent.png"]', 5000);
    }
    $this->assertNotEmpty($result, "Marker element present.");
  }

  /**
   * Tests the Marker clusterer.
   */
  public function testMarkerClusterer(): void {
    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    $layerFeature = $layerFeatureManager->getLayerFeature('marker_clusterer');

    $view = View::load('geolocation_demo_common_map');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features']['marker_clusterer'] = [
      'enabled' => TRUE,
      'settings' => $layerFeature->getSettings([]),
    ];
    $view->save();

    $this->drupalGet('geolocation-demo/common-map');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $googleErrorMessage = $this->assertSession()->waitForElement('css', '.gm-err-message');
    if ($googleErrorMessage) {
      $errors = $this->getSession()->evaluateScript("sessionStorage.getItem('geolocation_google_js_errors')");
      var_dump("\n" . $errors . "\n");
    }
    $this->assertEmpty($googleErrorMessage, "No Google error messages");

    $result = $this->assertSession()->waitForElementVisible('css', 'div[title^="Cluster"]', 5000);
    // Fails randomly, so just try again.
    if (empty($result)) {
      $this->drupalGet('geolocation-demo/common-map');
      $result = $this->assertSession()->waitForElementVisible('css', 'div[title^="Cluster"]', 5000);
    }
    $this->assertNotEmpty($result, "Cluster element present.");
  }

  /**
   * Tests the Marker clusterer.
   */
  public function testMarkerIconAdjustment(): void {
    /** @var \Drupal\geolocation\LayerFeatureManager $layerFeatureManager */
    $layerFeatureManager = \Drupal::service('plugin.manager.geolocation.layerfeature');

    /** @var \Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature\GoogleMarkerIcon $layerFeature */
    $layerFeature = $layerFeatureManager->getLayerFeature('marker_icon');

    $view = View::load('geolocation_demo_commonmap_with_marker_icons');
    $display = &$view->getDisplay('default');

    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features']['marker_icon'] = [
      'enabled' => TRUE,
      'settings' => $layerFeature->getSettings([]),
    ];

    $view->save();

    $this->drupalGet('geolocation-demo/common-map-marker-icons');

    $result = $this->assertSession()->waitForElementVisible('css', '.geolocation-map-container');
    $this->assertNotEmpty($result, "Container present.");

    $googleErrorMessage = $this->assertSession()->waitForElement('css', '.gm-err-message');
    if ($googleErrorMessage) {
      $errors = $this->getSession()->evaluateScript("sessionStorage.getItem('geolocation_google_js_errors')");
      var_dump("\n" . $errors . "\n");
    }
    $this->assertEmpty($googleErrorMessage, "No Google error messages");

    $result = $this->assertSession()->waitForElementVisible('css', 'img[src*="druplicon-nick-fury.png"]', 5000);

    $this->assertNotEmpty($result, "'Nick Fury' icon present.");

    $display['display_options']['style']['options']['map_provider_settings']['data_layers']['geolocation_default_layer:default']['settings']['features']['marker_icon'] = [
      'settings' => $layerFeature->getSettings([
        'size' => [
          'width' => 0,
          'height' => 0,
        ],
      ]),
    ];

    $view->save();

    $this->drupalGet('geolocation-demo/common-map-marker-icons');

    $this->assertSession()->elementNotExists('css', 'img[src*="druplicon-nick-fury.png"]');
  }

}
