<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the CustomField Type plugin manager.
 */
class CustomFieldTypeManager extends DefaultPluginManager implements CustomFieldTypeManagerInterface {

  /**
   * Constructs a new CustomFieldTypeManager object.
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
    parent::__construct(
      'Plugin/CustomFieldType',
      $namespaces,
      $module_handler,
      'Drupal\custom_field\Plugin\CustomFieldTypeInterface',
      'Drupal\custom_field\Annotation\CustomFieldType'
    );

    $this->alterInfo('custom_field_info');
    $this->setCacheBackend($cache_backend, 'customfield_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomFieldItems(array $settings): array {
    $items = [];
    $definitions = $this->getDefinitions();
    $field_settings = $settings['field_settings'] ?? [];

    // Table element rows weight property not working so lets
    // sort the data ahead of time in this function.
    $columns = $this->sortFieldsByWeight($settings['columns'], $field_settings);

    foreach ($columns as $name => $column) {
      unset($column['weight']);
      $settings = $field_settings[$name] ?? [];
      if (isset($settings['type']) && isset($definitions[$settings['type']])) {
        $type = $settings['type'];
      }
      else {
        switch ($column['type']) {
          case 'boolean':
            $type = 'checkbox';
            break;
          case 'color':
            $type = 'color';
            break;
          case 'email':
            $type = 'email';
            break;
          case 'decimal':
            $type = 'decimal';
            break;
          case 'float':
            $type = 'float';
            break;
          case 'integer':
            $type = 'integer';
            break;
          case 'map':
            $type = 'map_key_value';
            break;
          case 'string_long':
            $type = 'textarea';
            break;
          case 'uuid':
            $type = 'uuid';
            break;
          case 'uri':
            $type = 'url';
            break;
          default:
            $type = 'text';
        }
      }

      try {
        $items[$name] = $this->createInstance($type, [
          'name' => $column['name'],
          'max_length' => $column['max_length'],
          'unsigned' => $column['unsigned'],
          'data_type' => $column['type'],
          'precision' => $column['precision'],
          'scale' => $column['scale'],
          'widget_settings' => $settings['widget_settings'] ?? [],
          'formatter_settings' => $settings['formatter_settings'] ?? [],
        ]);
      }
      catch (PluginException $e) {
        // Should we log the error?
      }
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomFieldWidgetOptions($type): array {
    $options = [];
    $definitions = $this->getDefinitions();
    // Remove undefined widgets for data_type.
    foreach ($definitions as $key => $definition) {
      if (!in_array($type, $definition['data_types'])) {
        unset($definitions[$key]);
      }
    }
    // Sort the widgets by category and then by name.
    uasort($definitions, function ($a, $b) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      return strnatcasecmp($a['label'], $b['label']);
    });
    foreach ($definitions as $id => $definition) {
      $category = $definition['category'];
      // Add category grouping for multiple options.
      $options[(string) $category][$id] = $definition['label'];
    }
    if (count($options) <= 1) {
      $options = array_values($options)[0];
    }

    return $options;
  }

  /**
   * Sort fields by weight.
   *
   * @param array $columns1
   *   Columns from \Drupal\custom_field\Plugin\Field\FieldType\CustomItem settings.
   * @param array $field_settings
   *   Field settings \Drupal\custom_field\Plugin\Field\FieldType\CustomItem settings.
   *
   * @return array
   */
  private function sortFieldsByWeight(array $columns1, array $field_settings): array {
    $columns = [];
    foreach ($columns1 as $name => $column) {
      $weight = $field_settings[$name]['weight'] ?? 0;
      $column['weight'] = $weight;
      $columns[$name] = $column;
    }
    uasort($columns, function ($item1, $item2) {
      return $item1['weight'] <=> $item2['weight'];
    });

    return $columns;
  }

}
