<?php

namespace Drupal\geolocation;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LocationInput Base.
 *
 * @package Drupal\geolocation
 */
abstract class LocationInputBase extends PluginBase implements LocationInputInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
    protected FileSystemInterface $fileSystem,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): LocationInputInterface {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(array $settings): array {
    $default_settings = $this->getDefaultSettings();
    return array_replace_recursive($default_settings, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings = [], array $context = []): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array $values, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function getAvailableLocationInputOptions(array $context = []): array {
    return [
      $this->getPluginId() => $this->getPluginDefinition()['name'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCoordinates(array $form_value, array $settings, array $context = []): array {
    if (
      empty($form_value['coordinates'])
      || !is_array($form_value['coordinates'])
      || !isset($form_value['coordinates']['lat'])
      || !isset($form_value['coordinates']['lng'])
      || $form_value['coordinates']['lng'] === ''
      || $form_value['coordinates']['lat'] === ''
    ) {
      return [];
    }

    return [
      'lat' => (float) $form_value['coordinates']['lat'],
      'lng' => (float) $form_value['coordinates']['lng'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array $form, array $settings, array $context = [], array $default_value = NULL): array {
    $settings = $this->getSettings($settings);

    $path = $this->getJavascriptModulePath();

    if ($path) {
      $form = BubbleableMetadata::mergeAttachments($form, [
        '#attached' => [
          'drupalSettings' => [
            'geolocation' => [
              'locationInput' => [
                $form['#attributes']['data-identifier'] => [
                  $this->getPluginId() => [
                    'import_path' => $path,
                    'settings' => $settings,
                  ],
                ],
              ],
            ],
          ],
        ],
      ]);
    }

    return $form;
  }

  /**
   * Get the path to load JS module.
   *
   * @return string|null
   *   JS Module path.
   */
  protected function getJavascriptModulePath() : ?string {
    $class_name = (new \ReflectionClass($this))->getShortName();

    $module_path = $this->moduleHandler->getModule($this->getPluginDefinition()['provider'])->getPath();

    if (!file_exists($this->fileSystem->realpath($module_path) . '/js/LocationInput/' . $class_name . '.js')) {
      return NULL;
    }

    return base_path() . $module_path . '/js/LocationInput/' . $class_name . '.js';
  }

}
