<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\GeocoderManager;
use Drupal\geolocation\MapFeatureInterface;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\Plugin\geolocation\MapFeature\ControlCustomElementBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Geocoding control element.
 *
 * @MapFeature(
 *   id = "control_geocoder",
 *   name = @Translation("Map Control - Geocoder"),
 *   description = @Translation("Add address search with geocoding functionality map."),
 *   type = "google_maps",
 * )
 */
class GoogleControlCustomGeocoder extends ControlCustomElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    FileSystemInterface $file_system,
    Token $token,
    LibraryDiscovery $libraryDiscovery,
    protected GeocoderManager $geocoderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $file_system, $token, $libraryDiscovery);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MapFeatureInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('library.discovery'),
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return array_replace_recursive(
      parent::getDefaultSettings(),
      [
        'geocoder' => 'google_geocoding_api',
        'geocoder_settings' => [],
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings, MapProviderInterface $mapProvider = NULL): array {
    $settings = parent::getSettings($settings, $mapProvider);

    $geocoder_plugin = $this->geocoderManager->getGeocoder($settings['geocoder'] ?? '', $settings['geocoder_settings'] ?? []);
    if ($geocoder_plugin) {
      $settings['geocoder_settings'] = array_replace_recursive($geocoder_plugin->getSettings(), $settings['geocoder_settings']);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $settings = $this->getSettings($settings);

    $geocoder_options = [];
    foreach ($this->geocoderManager->getDefinitions() as $id => $definition) {
      if (empty($definition['frontendCapable'])) {
        continue;
      }
      $geocoder_options[$id] = $definition['name'];
    }

    if (!$geocoder_options) {
      return $form;
    }

    $form['geocoder'] = [
      '#type' => 'select',
      '#options' => $geocoder_options,
      '#title' => $this->t('Geocoder plugin'),
      '#default_value' => $settings['geocoder'],
      '#ajax' => [
        'callback' => [
          get_class($this->geocoderManager), 'addGeocoderSettingsFormAjax',
        ],
        'wrapper' => $this->getPluginId() . '-geocoder-plugin-settings',
        'effect' => 'fade',
      ],
    ];

    $geocoder_plugin = $this->geocoderManager->getGeocoder(
      $settings['geocoder'],
      $settings['geocoder_settings']
    );

    if ($geocoder_plugin) {
      $form['geocoder_settings'] = $geocoder_plugin->getOptionsForm();
    }
    else {
      $form['geocoder_settings'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t("No settings available."),
      ];
    }

    $form['geocoder_settings'] = array_replace_recursive($form['geocoder_settings'], [
      '#flatten' => TRUE,
      '#prefix' => '<div id="' . $this->getPluginId() . '-geocoder-plugin-settings">',
      '#suffix' => '</div>',
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings = [], array $context = [], MapProviderInterface $mapProvider = NULL): array {
    $render_array = parent::alterMap($render_array, $feature_settings, $context, $mapProvider);

    $geocoder_plugin = $this->geocoderManager->getGeocoder($feature_settings['geocoder'], $feature_settings['geocoder_settings']);
    if (empty($geocoder_plugin)) {
      return $render_array;
    }

    $geocoder_plugin->alterRenderArray($render_array['#controls'][$this->pluginId], $render_array['#id']);

    return $render_array;
  }

}
