<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Search plugin manager.
 *
 * @method LocationInterface createInstance($plugin_id, array $configuration = [])
 */
class LocationManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Constructs an LocationManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/geolocation/Location', $namespaces, $module_handler, 'Drupal\geolocation\LocationInterface', 'Drupal\geolocation\Annotation\Location');
    $this->alterInfo('geolocation_location_info');
    $this->setCacheBackend($cache_backend, 'geolocation_location');
  }

  /**
   * Return Location by ID.
   *
   * @param string $id
   *   Location ID.
   * @param array $configuration
   *   Configuration.
   *
   * @return \Drupal\geolocation\LocationInterface|null
   *   Location instance.
   */
  public function getLocationPlugin(string $id, array $configuration = []): ?LocationInterface {
    if (!$this->hasDefinition($id)) {
      return NULL;
    }
    try {
      return $this->createInstance($id, $configuration);
    }
    catch (\Exception $e) {
      \Drupal::logger('geolocation')->warning($e->getMessage());
      return NULL;
    }
  }

  /**
   * Get form render array.
   *
   * @param array $settings
   *   Settings.
   * @param array $context
   *   Optional context.
   *
   * @return array
   *   Form.
   */
  public function getLocationOptionsForm(array $settings, array $context = []): array {
    $form = [
      '#type' => 'table',
      '#caption' => $this->t('<b>Centre options</b>'),
      '#prefix' => $this->t('Please note: Each option will, if it can be applied, supersede any following option.'),
      '#header' => [
        $this->t('Enable'),
        $this->t('Option'),
        $this->t('Settings'),
        $this->t('Settings'),
      ],
      '#attributes' => ['id' => 'geolocation-centre-options'],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'geolocation-location-weight',
        ],
      ],
    ];

    foreach ($this->getDefinitions() as $location_id => $location_definition) {
      $location = $this->createInstance($location_id);
      foreach ($location->getAvailableLocationOptions($context) as $option_id => $label) {
        $option_enable_id = uniqid($option_id . '_enabled');
        $weight = $settings[$option_id]['weight'] ?? 0;
        $form[$option_id] = [
          '#weight' => $weight,
          '#attributes' => [
            'class' => [
              'draggable',
            ],
          ],
          'enable' => [
            '#attributes' => [
              'id' => $option_enable_id,
            ],
            '#type' => 'checkbox',
            '#default_value' => $settings[$option_id]['enable'] ?? FALSE,
          ],
          'option' => [
            '#markup' => $label,
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @option', ['@option' => $label]),
            '#title_display' => 'invisible',
            '#size' => 4,
            '#default_value' => $weight,
            '#attributes' => ['class' => ['geolocation-location-weight']],
          ],
          'location_plugin_id' => [
            '#type' => 'value',
            '#value' => $location_id,
          ],
        ];

        $option_form = $location->getSettingsForm(
          $option_id,
          $settings[$option_id]['settings'] ?? [],
          $context
        );

        if (!empty($option_form)) {
          $option_form['#states'] = [
            'visible' => [
              ':input[id="' . $option_enable_id . '"]' => ['checked' => TRUE],
            ],
          ];
          $option_form['#type'] = 'item';

          $form[$option_id]['settings'] = $option_form;
        }
      }
    }

    uasort($form, [SortArray::class, 'sortByWeightProperty']);

    return $form;
  }

  /**
   * Get location center coordinates.
   *
   * @param array $settings
   *   Center option settings.
   * @param array $context
   *   Context.
   *
   * @return array|null
   *   Centre value.
   */
  public function getLocation(array $settings, array $context = []): ?array {
    $center = [];

    foreach ($settings as $option_id => $option) {
      // Ignore if not enabled.
      if (empty($option['enable'])) {
        continue;
      }

      if (!$this->hasDefinition($option['location_plugin_id'])) {
        continue;
      }

      $location_plugin = $this->createInstance($option['location_plugin_id']);
      $plugin_center = $location_plugin->getCoordinates($option_id, empty($option['settings']) ? [] : $option['settings'], $context);

      if (!empty($plugin_center)) {
        // Break on first found center.
        return $plugin_center;
      }
    }

    return $center;
  }

}
