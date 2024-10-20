<?php

namespace Drupal\geolocation;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Search plugin manager.
 *
 * @method DataProviderInterface createInstance($plugin_id, array $configuration = [])
 */
class DataProviderManager extends DefaultPluginManager {

  use LoggerChannelTrait;

  /**
   * Constructs an DataProviderManager object.
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
    parent::__construct('Plugin/geolocation/DataProvider', $namespaces, $module_handler, 'Drupal\geolocation\DataProviderInterface', 'Drupal\geolocation\Annotation\DataProvider');
    $this->alterInfo('geolocation_dataprovider_info');
    $this->setCacheBackend($cache_backend, 'geolocation_dataprovider');
  }

  /**
   * Get data provider by views field.
   *
   * @param \Drupal\views\Plugin\views\field\FieldPluginBase $viewField
   *   Views field.
   * @param array $configuration
   *   Configuration.
   *
   * @return ?\Drupal\geolocation\DataProviderInterface
   *   Data provider.
   */
  public function getDataProviderByViewsField(FieldPluginBase $viewField, array $configuration = []): ?DataProviderInterface {
    $definitions = $this->getDefinitions();
    try {
      foreach ($definitions as $dataProviderId => $dataProviderDefinition) {
        $dataProvider = $this->createInstance($dataProviderId, $configuration);

        if ($dataProvider->isViewsGeoOption($viewField)) {
          $dataProvider->setViewsField($viewField);
          return $dataProvider;
        }
      }
    }
    catch (\Exception $e) {
      $this->getLogger('geolocation')->warning($e->getMessage());
      return NULL;
    }

    return NULL;
  }

  /**
   * Get data provider by field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   Field definition.
   * @param array $configuration
   *   Configuration.
   *
   * @return ?\Drupal\geolocation\DataProviderInterface
   *   Data provider.
   */
  public function getDataProviderByFieldDefinition(FieldDefinitionInterface $fieldDefinition, array $configuration = []): ?DataProviderInterface {
    $definitions = $this->getDefinitions();
    try {
      foreach ($definitions as $dataProviderId => $dataProviderDefinition) {
        $dataProvider = $this->createInstance($dataProviderId, $configuration);

        if ($dataProvider->isFieldGeoOption($fieldDefinition)) {
          $dataProvider->setFieldDefinition($fieldDefinition);
          return $dataProvider;
        }
      }
    }
    catch (\Exception $e) {
      $this->getLogger('geolocation')->warning($e->getMessage());
      return NULL;
    }

    return NULL;
  }

  /**
   * Data provider settings form AJAX endpoint.
   *
   * @param array $form
   *   Render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return ?array
   *   Render array.
   */
  public static function addDataProviderSettingsFormAjax(array $form, FormStateInterface $form_state): ?array {
    $triggering_element_parents = $form_state->getTriggeringElement()['#array_parents'];

    $settings_element_parents = $triggering_element_parents;
    array_pop($settings_element_parents);
    $settings_element_parents[] = 'data_provider_settings';

    return NestedArray::getValue($form, $settings_element_parents);
  }

}
