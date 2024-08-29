<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\geolocation\Element\GeolocationMap;
use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\LayerFeatureInterface;
use Drupal\geolocation\MapProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Google Maps.
 *
 * @LayerFeature(
 *   id = "marker_icon",
 *   name = @Translation("Marker Icon Adjustment"),
 *   description = @Translation("Icon properties."),
 *   type = "google_maps",
 * )
 */
class GoogleMarkerIcon extends LayerFeatureBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandler $module_handler,
    FileSystemInterface $file_system,
    Token $token,
    LibraryDiscovery $libraryDiscovery,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $file_system, $token, $libraryDiscovery);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LayerFeatureInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('token'),
      $container->get('library.discovery'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'marker_icon_path' => '',
      'anchor' => [
        'x' => 0,
        'y' => 0,
      ],
      'origin' => [
        'x' => 0,
        'y' => 0,
      ],
      'label_origin' => [
        'x' => 0,
        'y' => 0,
      ],
      'size' => [
        'width' => NULL,
        'height' => NULL,
      ],
      'scaled_size' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['marker_icon_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon path'),
      '#description' => $this->t('Set relative or absolute path to custom marker icon. Tokens supported. Empty for default. Attention: In views contexts, additional icon source options are available in the style settings.'),
      '#default_value' => $settings['marker_icon_path'],
    ];

    $form['anchor'] = [
      '#type' => 'item',
      '#description' => $this->t('The position at which to anchor an image in correspondence to the location of the marker on the map. By default, the anchor is located along the center point of the bottom of the image.'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Anchor - X'),
        '#default_value' => $settings['anchor']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Anchor - Y'),
        '#default_value' => $settings['anchor']['y'],
      ],
    ];
    $form['origin'] = [
      '#type' => 'item',
      '#description' => $this->t('The position of the image within a sprite, if any. By default, the origin is located at the top left corner of the image (0, 0).'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Origin - X'),
        '#default_value' => $settings['origin']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Origin - Y'),
        '#default_value' => $settings['origin']['y'],
      ],
    ];
    $form['label_origin'] = [
      '#type' => 'item',
      '#description' => $this->t('The origin of the label relative to the top-left corner of the icon image, if a label is supplied by the marker. By default, the origin is located in the center point of the image.'),
      'x' => [
        '#type' => 'number',
        '#title' => $this->t('Label Origin - X'),
        '#default_value' => $settings['label_origin']['x'],
      ],
      'y' => [
        '#type' => 'number',
        '#title' => $this->t('Label Origin - Y'),
        '#default_value' => $settings['label_origin']['y'],
      ],
    ];
    $form['size'] = [
      '#type' => 'item',
      '#description' => $this->t('The display size of the sprite or image. When using sprites, you must specify the sprite size. If the size is not provided, it will be set when the image loads.'),
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Size - Width'),
        '#default_value' => $settings['size']['width'],
        '#min' => 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Size - Height'),
        '#default_value' => $settings['size']['height'],
        '#min' => 0,
      ],
    ];
    $form['scaled_size'] = [
      '#type' => 'item',
      '#description' => $this->t('The size of the entire image after scaling, if any. Use this property to stretch/shrink an image or a sprite.'),
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Scaled Size - Width'),
        '#default_value' => $settings['scaled_size']['width'],
        '#min' => 0,
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Scaled Size - Height'),
        '#default_value' => $settings['scaled_size']['height'],
        '#min' => 0,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLayer(array $render_array, string $layer_id, array $feature_settings = [], array $context = []): array {
    $render_array = parent::alterLayer($render_array, $layer_id, $feature_settings, $context);

    if (!empty($feature_settings['marker_icon_path'])) {
      $path = $this->token->replace($feature_settings['marker_icon_path'], $context);
      $path = $this->fileUrlGenerator->generateAbsoluteString($path);
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$render_array['#id']]['dataLayers'][$layer_id]['features'][$this->getPluginId()]['markerIconPath'] = $path;

      foreach (GeolocationMap::getLocations($render_array['#layers'][$layer_id]) as &$location) {
        if (empty($location['#icon'])) {
          $location['#icon'] = $path;
        }
      }
    }

    return $render_array;
  }

}
