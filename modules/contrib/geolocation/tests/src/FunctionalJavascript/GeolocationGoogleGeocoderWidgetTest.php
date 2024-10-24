<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\User;

/**
 * Tests the Google Geocoder Widget functionality.
 *
 * @group geolocation
 */
class GeolocationGoogleGeocoderWidgetTest extends GeolocationJavascriptTestBase {

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User
   */
  public User $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'geolocation',
    'geolocation_test_views',
    'geolocation_google_maps',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add the geolocation field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_geolocation',
      'entity_type' => 'node',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_geolocation',
      'label' => 'Geolocation',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'map_provider_id' => 'google_maps',
        ],
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
      ])
      ->save();

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity_test_storage->create([
      'id' => 1,
      'title' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'foo test',
      'body' => 'bar test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'bar',
      'body' => 'test foobar',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 4,
      'title' => 'Custom map settings',
      'body' => 'This content tests if the custom map settings are respected',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
        'data' => [
          'google_map_settings' => [
            'height' => '376px',
            'width' => '229px',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests the Google Maps widget.
   */
  public function testGeocoderWidgetMapPresent(): void {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/3/edit');

    $anchor = $this->assertSession()->waitForElement('css', 'a[href^="https://maps.google.com"][href*="hl="]');
    $this->assertNotEmpty($anchor, "Wait for GoogleMaps to be loaded.");

    $this->assertSession()->elementExists('css', '.geolocation-map-container');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-map-container [class^="gm-"]');
  }

  /**
   * Tests the Google Maps widget.
   */
  public function testGeocoderWidgetEmptyValuePreserved(): void {
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'default_latitude' => 12,
          'default_longitude' => 24,
        ],
      ])
      ->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/add/article');

    $page = $this->getSession()->getPage();
    $page->fillField('title[0][value]', 'I am new');
    $page->pressButton('Save');

    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = \Drupal::entityTypeManager()->getStorage('node')->load(5);
    $this->assertSession()->assert($new_node->get('field_geolocation')->isEmpty(), "Node geolocation field empty after saving from predefined location widget");
  }

}
