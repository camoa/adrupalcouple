<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geolocation\MapProviderInterface;
use Drupal\geolocation\MapProviderManager;
use Drupal\views\FieldAPIHandlerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Map geometry widget base.
 */
abstract class GeolocationGeometryWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  use FieldAPIHandlerTrait;

  /**
   * Map provider ID.
   *
   * @var string
   */
  static protected string $mapProviderId;

  /**
   * Map provider settings ID.
   *
   * @var string
   */
  static protected string $mapProviderSettingsFormId;

  /**
   * Map provider.
   *
   * @var \Drupal\geolocation\MapProviderInterface
   */
  protected MapProviderInterface $mapProvider;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected MapProviderManager $mapProviderManager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    if (!empty(static::$mapProviderId)) {
      $this->mapProvider = $this->mapProviderManager->getMapProvider(static::$mapProviderId);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): GeolocationGeometryWidgetBase {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.geolocation.mapprovider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      static::$mapProviderSettingsFormId => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    $settings = parent::getSettings();
    $map_settings = [];
    if (!empty($settings[static::$mapProviderSettingsFormId])) {
      $map_settings = $settings[static::$mapProviderSettingsFormId];
    }

    return NestedArray::mergeDeep(
      $settings,
      [
        static::$mapProviderSettingsFormId => $this->mapProvider->getSettings($map_settings),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();
    $element = parent::settingsForm($form, $form_state);

    $element[static::$mapProviderSettingsFormId] = $this->mapProvider->getSettingsForm(
      $settings[static::$mapProviderSettingsFormId],
      [
        'fields',
        $this->fieldDefinition->getName(),
        'settings_edit_form',
        'settings',
        static::$mapProviderSettingsFormId,
      ]
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $settings = $this->getSettings();

    $map_provider_settings = empty($settings[static::$mapProviderSettingsFormId]) ? [] : $settings[static::$mapProviderSettingsFormId];

    return array_replace_recursive($summary, $this->mapProvider->getSettingsSummary($map_provider_settings));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {

    $element['#type'] = 'container';
    $element['#attributes'] = [
      'data-geometry-type' => str_replace('geolocation_geometry_', '', $this->fieldDefinition->getType()),
      'class' => [
        str_replace('_', '-', $this->getPluginId()) . '-geojson',
      ],
    ];

    $element['geojson'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GeoJSON'),
      '#default_value' => $items[$delta]->geojson ?? NULL,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#attributes' => [
        'class' => [
          'geolocation-geometry-widget-geojson-input',
          str_replace('_', '-', $this->getPluginId()) . '-geojson-input',
        ],
      ],
    ];

    $element['map'] = [
      '#type' => 'geolocation_map',
      '#maptype' => static::$mapProviderId,
      '#weight' => -10,
      '#settings' => $this->getSettings()[static::$mapProviderSettingsFormId],
      '#context' => ['widget' => $this],
      '#attributes' => [
        'class' => [
          str_replace('_', '-', $this->getPluginId()) . '-geojson-map',
        ],
      ],
    ];

    return $element;
  }

}
