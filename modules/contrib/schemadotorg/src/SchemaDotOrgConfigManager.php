<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Schema.org config manager service.
 */
class SchemaDotOrgConfigManager implements SchemaDotOrgConfigManagerInterface {

  /**
   * Constructs a SchemaDotOrgConfigManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function setSchemaTypeDefaultProperties(string $schema_type, array|string|NULL $add = NULL, array|string|NULL $remove = NULL): void {
    $config = $this->configFactory->getEditable('schemadotorg.settings');

    // Get default properties.
    $default_properties = $config->get("schema_types.default_properties.$schema_type") ?? [];

    // Add/remove default properties.
    $this->updateProperties($default_properties, $add, $remove);

    // Save default properties.
    $config->set("schema_types.default_properties.$schema_type", $default_properties)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function setMappingTypeSchemaTypeDefaultProperties(string $entity_type_id, string $schema_type, array|string|NULL $add = NULL, array|string|NULL $remove = NULL): void {
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeInterface $mapping_type */
    $mapping_type = \Drupal::entityTypeManager()
      ->getStorage('schemadotorg_mapping_type')
      ->load($entity_type_id);

    // Get default properties from mapping type.
    $default_schema_type_properties = $mapping_type->get('default_schema_type_properties');
    $default_properties = $default_schema_type_properties[$schema_type] ?? [];

    // Add/remove default properties.
    $this->updateProperties($default_properties, $add, $remove);

    // Save default properties to mapping type.
    $default_schema_type_properties[$schema_type] = $default_properties;
    $mapping_type->set('default_schema_type_properties', $default_schema_type_properties);
    $mapping_type->save();
  }

  /**
   * Update Schema.org properties.
   *
   * @param array $properties
   *   An array of Schema.org properties.
   * @param array|string|null $add
   *   Schema.org properties to be removed.
   * @param array|string|null $remove
   *   Schema.org properties to be added.
   */
  protected function updateProperties(array &$properties, array|string|NULL $add = NULL, array|string|NULL $remove = NULL): void {
    // Remove default properties.
    if ($remove) {
      $remove = (array) $remove;
      $properties = array_filter($properties, function ($property) use ($remove) {
        return !in_array($property, $remove);
      });
    }

    // Add default properties.
    if ($add) {
      $add = (array) $add;
      $properties = array_merge($properties, $add);
      $properties = array_unique($properties);
    }

    // Sort default properties.
    sort($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function repair(): void {
    // Default properties sorted by path/breadcrumb.
    $config = $this->configFactory->getEditable('schemadotorg.settings');
    $default_properties = $config->get('schema_types.default_properties');
    $paths = [];
    foreach (array_keys($default_properties) as $type) {
      $breadcrumbs = $this->schemaTypeManager->getTypeBreadcrumbs($type);
      $path = array_key_first($breadcrumbs);
      $paths[$path] = $type;
    }
    ksort($paths);
    $sorted_default_properties = [];
    foreach ($paths as $type) {
      $properties = array_unique($default_properties[$type]);
      sort($properties);
      $sorted_default_properties[$type] = array_unique($properties);
    }
    $config->set('schema_types.default_properties', $sorted_default_properties);
    $config->save();

    // Config sorting.
    $config_sort = [
      'schemadotorg.settings' => [
        'ksort' => [
          'schema_types.main_properties',
          'schema_properties.range_includes',
          'schema_properties.default_fields',
        ],
        'sort' => [
          'schema_properties.ignored_properties',
        ],
      ],
      'schemadotorg.names' => [
        'ksort' => [
          'custom_words',
          'custom_names',
          'prefixes',
          'suffixes',
          'abbreviations',
        ],
        'sort' => [
          'acronyms',
          'minor_words',
        ],
      ],
    ];
    foreach ($config_sort as $config_name => $sort) {
      $config = $this->configFactory->getEditable($config_name);
      foreach ($sort as $method => $keys) {
        foreach ($keys as $key) {
          $value = $config->get($key);
          if (!$value) {
            throw new \Exception('Unable to locate ' . $key);
          }
          $method($value);
          $config->set($key, $value);
        }
      }
      $config->save();
    }
  }

}
