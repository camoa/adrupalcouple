<?php

namespace Drupal\geolocation\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geolocation\GeocoderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for geolocation.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("geolocation_geocoder_argument")
 */
class GeocoderArgument extends ProximityArgument implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected GeocoderManager $geocoderManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): GeocoderArgument {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    return array_replace_recursive(
      parent::defineOptions(),
      [
        'geocoder' => ['default' => 'google_geocoding_api'],
        'geocoder_settings' => ['default' => []],
      ]
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    parent::buildOptionsForm($form, $form_state);

    $geocoder_options = [];
    foreach ($this->geocoderManager->getDefinitions() as $id => $definition) {
      if (empty($definition['frontendCapable'])) {
        continue;
      }
      $geocoder_options[$id] = $definition['name'];
    }

    if ($geocoder_options) {
      $form['geocoder'] = [
        '#type' => 'select',
        '#options' => $geocoder_options,
        '#title' => $this->t('Geocoder plugin'),
        '#default_value' => $this->options['geocoder'],
        '#ajax' => [
          'callback' => [
            get_class($this->geocoderManager), 'addGeocoderSettingsFormAjax',
          ],
          'wrapper' => 'argument-geocoder-plugin-settings',
          'effect' => 'fade',
        ],
      ];

      if (!empty($this->options['geocoder'])) {
        $geocoder_plugin = $this->geocoderManager->getGeocoder(
          $this->options['geocoder'],
          $this->options['geocoder_settings'] ?: []
        );
      }
      elseif (current(array_keys($geocoder_options))) {
        $geocoder_plugin = $this->geocoderManager->getGeocoder(current(array_keys($geocoder_options)));
      }

      if (!empty($geocoder_plugin)) {
        $geocoder_settings_form = $geocoder_plugin->getOptionsForm();
        if ($geocoder_settings_form) {
          $form['geocoder_settings'] = $geocoder_settings_form;
        }
      }

      if (empty($form['geocoder_settings'])) {
        $form['geocoder_settings'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t("No settings available."),
        ];
      }

      $form['geocoder_settings'] = array_replace_recursive($form['geocoder_settings'], [
        '#flatten' => TRUE,
        '#prefix' => '<div id="argument-geocoder-plugin-settings">',
        '#suffix' => '</div>',
      ]);
    }
  }

  /**
   * Processes the passed argument into an array of relevant geolocation data.
   *
   * @return array
   *   The calculated values.
   */
  public function getParsedReferenceLocation(): array {
    // Cache the vales so this only gets processed once.
    static $values;

    if (!isset($values)) {
      $matches = [];
      preg_match('/^([^<>=]+)([<>=]+)([0-9.]+)(.*$)/', $this->getValue(), $matches);

      if (count($matches) < 4) {
        return [];
      }

      $geocoder = $this->geocoderManager->getGeocoder($this->options['geocoder'], $this->options['geocoder_settings']);
      if (empty($geocoder)) {
        return [];
      }

      $coordinates = $geocoder->geocode($matches[1]);
      if (empty($coordinates)) {
        return [];
      }

      $values = $coordinates['location'];

      if (in_array($matches[2], [
        '<>',
        '=',
        '>=',
        '<=',
        '>',
        '<',
      ])) {
        $values['operator'] = $matches[2];
      }
      else {
        return [];
      }
      $values['distance'] = floatval($matches[3]);
      $values['unit'] = empty($matches[4]) ? 'km' : $matches[4];
    }

    return $values;
  }

}
