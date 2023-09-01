<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg\Utility\SchemaDotOrgStringHelper;

/**
 * Schema.org installer service.
 */
class SchemaDotOrgInstaller implements SchemaDotOrgInstallerInterface {
  use StringTranslationTrait;

  /**
   * Schema.org version.
   */
  const VERSION = '22.0';

  /**
   * Constructs a SchemaDotOrgInstaller object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   */
  public function __construct(
    protected Connection $database,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function requirements(string $phase): ?array {
    if ($phase !== 'runtime') {
      return NULL;
    }

    $requirements = [];
    $this->checkRecommendedRequirements($requirements);
    $this->checkIntegrationRequirements($requirements);
    return $requirements;
  }

  /**
   * Check for recommended modules.
   *
   * @param array &$requirements
   *   An associative array of requirements for status reporting.
   */
  protected function checkRecommendedRequirements(array &$requirements): void {
    // NOTE: Suggestions are also included the Schema.org Blueprints
    // composer.json file.
    $recommended_modules = [
      'datetime' => [
        'title' => $this->t('Datetime'),
        'description' => $this->t('Defines datetime form elements and a datetime field type.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/datetime',
      ],
      'link' => [
        'title' => $this->t('Link'),
        'description' => $this->t('Provides a simple link field type.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/link',
      ],
      'media' => [
        'title' => $this->t('Media'),
        'description' => $this->t('Manages the creation, configuration, and display of media items.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/media',
      ],
      'media_library' => [
        'title' => $this->t('Media Library'),
        'description' => $this->t('Enhances the media list with additional features to more easily find and use existing media items.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/media_library',
      ],
      'telephone' => [
        'title' => $this->t('Telephone'),
        'description' => $this->t('Defines a field type for telephone numbers.'),
        'uri' => 'https://www.drupal.org/docs/8/core/modules/telephone',
      ],
      'address' => [
        'title' => $this->t('Address'),
        'description' => $this->t('Provides functionality for storing, validating and displaying international postal addresses.'),
        'uri' => 'https://www.drupal.org/project/address',
      ],
    ];

    $installed_modules = $this->moduleHandler->getModuleList();
    $missing_modules = array_diff_key($recommended_modules, $installed_modules);
    if (!$missing_modules) {
      return;
    }

    $module_names = [];
    $module_items = [];
    foreach ($missing_modules as $missing_module) {
      $module_names[] = $missing_module['title'];
      $module_items[] = [
        'title' => [
          '#type' => 'link',
          '#title' => $missing_module['title'],
          '#url' => Url::fromUri($missing_module['uri']),
          '#suffix' => '</br>',
        ],
        'description' => [
          '#markup' => $missing_module['description'],
        ],
      ];
    }
    $requirements['schemadotorg_recommended_modules'] = [
      'title' => $this->t('Schema.org Blueprints: Recommended modules missing'),
      'value' => $this->t('Recommended modules missing: %module_list.', ['%module_list' => implode(', ', $module_names)]),
      'description' => [
        'content' => [
          '#markup' => $this->t('The below recommended modules help integrate and support Schema.org mappings, entities, and fields.'),
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $module_items,
        ],
      ],
      'severity' => REQUIREMENT_WARNING,
    ];
  }

  /**
   * Check for integration modules requirements.
   *
   * @param array &$requirements
   *   An associative array of requirements for status reporting.
   */
  protected function checkIntegrationRequirements(array &$requirements): void {
    $integration_modules = [
      'media' => [
        'title' => $this->t('Schema.org Blueprints Media'),
        'description' => $this->t('Integrates the Media and Media Library module with the Schema.org Blueprints module.'),
        'uri' => 'https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_media',
      ],
      'paragraphs' => [
        'title' => $this->t('Schema.org Blueprints Paragraphs'),
        'description' => $this->t('Integrates the Paragraphs and Paragraphs Library module with the Schema.org Blueprints module.'),
        'uri' => 'https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_paragraphs',
      ],
      'taxonomy' => [
        'title' => $this->t('Schema.org Blueprints Taxonomy'),
        'description' => $this->t('Assists with creating and mapping taxonomy vocabularies and terms.'),
        'uri' => 'https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_taxonomy)**',
      ],
    ];
    foreach ($integration_modules as $module_name => $intergration_module) {
      if (!$this->moduleHandler->moduleExists($module_name)
        || $this->moduleHandler->moduleExists('schemadotorg_' . $module_name)) {
        unset($integration_modules[$module_name]);
      }
    }
    if (!$integration_modules) {
      return;
    }

    $module_names = [];
    $module_items = [];
    foreach ($integration_modules as $integration_module) {
      $module_names[] = $integration_module['title'];
      $module_items[] = [
        'title' => [
          '#type' => 'link',
          '#title' => $integration_module['title'],
          '#url' => Url::fromUri($integration_module['uri']),
          '#suffix' => '</br>',
        ],
        'description' => [
          '#markup' => $integration_module['description'],
        ],
      ];
    }
    $requirements['schemadotorg_integration_modules'] = [
      'title' => $this->t('Schema.org Blueprints: Integration modules missing'),
      'value' => $this->t('Integration modules missing: %module_list.', ['%module_list' => implode(', ', $module_names)]),
      'description' => [
        'content' => [
          '#markup' => $this->t('The below modules are required to support Schema.org mappings and entities.'),
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $module_items,
        ],
      ],
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function install(): void {
    // Recreate Schema.org types and properties tables.
    // Recreating these readonly tables allows us to continually refine and
    // optimize the table schemas.
    $this->reinstallSchema();

    // Import Schema.org types and properties tables.
    $this->importTable('types');
    $this->importTable('properties');
  }

  /**
   * {@inheritdoc}
   */
  public function installModules(?array $modules = NULL): void {
    $modules = $modules ?? array_keys($this->moduleHandler->getModuleList());
    // Create default mapping type for modules that provide content entities
    // that could be mapped to Schema.org types.
    $mapping_types = [
      // Module name.
      'storage' => [
        // Target entity type id.
        'storage' => [
          // Mapping type default values.
          'default_base_fields' => [
            'uuid' => [],
            'user_id' => [],
            'langcode' => ['inLanguage'],
            'name' => ['name', 'headline', 'title'],
            'created' => ['dateCreated'],
            'changed' => ['dateModified'],
          ],
        ],
      ],
    ];
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingTypeStorageInterface $mapping_type_storage */
    $mapping_type_storage = $this->entityTypeManager->getStorage('schemadotorg_mapping_type');
    foreach ($modules as $module) {
      if (isset($mapping_types[$module])) {
        foreach ($mapping_types[$module] as $target_entity_type_id => $values) {
          $values = $mapping_types[$module] + [
            'multiple' => FALSE,
            'target_entity_type_id' => $target_entity_type_id,
          ];
          if (!$mapping_type_storage->load($target_entity_type_id)) {
            $mapping_type_storage->create($values)->save();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function schema(): array {
    $schema = [];

    // Schema.org: Types.
    // @see https://raw.githubusercontent.com/schemaorg/schemaorg/main/data/releases/13.0/schemaorg-current-https-types.csv
    $schema['schemadotorg_types'] = [
      'description' => 'Schema.org types',
      'fields' => [
        'id' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
        'label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'comment' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_type_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'enumerationtype' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'equivalent_class' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'properties' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_types' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'supersedes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'superseded_by' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'is_part_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
      ],
    ];
    // Schema.org: Properties.
    // @see https://raw.githubusercontent.com/schemaorg/schemaorg/main/data/releases/13.0/schemaorg-current-https-properties.csv
    $schema['schemadotorg_properties'] = [
      'description' => 'Schema.org properties',
      'fields' => [
        'id' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
        ],
        'label' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'comment' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'sub_property_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'equivalent_property' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'subproperties' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'domain_includes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'range_includes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'inverse_of' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'supersedes' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
        'superseded_by' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'is_part_of' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'label' => ['label'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadCsvData(): void {
    $version = static::VERSION;

    $directory = __DIR__ . "/../data/$version";
    if (!file_exists($directory)) {
      mkdir($directory);
    }

    $tables = ['properties', 'types'];
    foreach ($tables as $table) {
      $source = "https://github.com/schemaorg/schemaorg/blob/main/data/releases/$version/schemaorg-all-https-$table.csv?raw=true";
      $destination = "$directory/schemaorg-current-https-$table.csv";
      copy($source, $destination);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function translateCsvData(): void {
    $tables = ['properties', 'types'];
    $strings = [];
    foreach ($tables as $table) {
      $result = $this->database
        ->select('schemadotorg_' . $table, 'table')
        ->fields('table', ['id', 'label', 'comment'])
        ->orderBy('label')
        ->execute();
      while ($record = $result->fetchAssoc()) {
        $strings[] = '// ' . $record['id'];
        $strings[] = $this->formatStringTranslation($record['label']);
        $strings[] = $this->formatStringTranslation($record['comment']);
        $first_sentence = SchemaDotOrgStringHelper::getFirstSentence($record['comment']);
        if ($record['comment'] !== $first_sentence) {
          $strings[] = $this->formatStringTranslation($first_sentence);
        }
        $strings[] = '';
      }
      $filename = __DIR__ . '/../data/' . static::VERSION . '/schemaorgdotorg.translations.' . $table . '.inc';
      $translatable_strings = implode(PHP_EOL, $strings);
      $contents = <<<EOT
        <?php

        /**
         * @file
         * Translate Schema.org $table id and comment strings.
         *
         * Passing Schema.org label and comments to Drupal's t() function to
         * ensure all translatable Schema.org strings discovered by
         * localize.drupal.org.
         *
         * DO NOT EDIT.
         * This file is generated using `drush schemadotorg:translate-schema`.
         *
         * @see https://localize.drupal.org/translate/languages/en-gb/translate?project=schemadotorg
         */

        declare(strict_types = 1);

        // phpcs:disable Drupal.Semantics.FunctionT.BackslashSingleQuote

        $translatable_strings
        EOT;
      file_put_contents($filename, $contents);
    }
  }

  /**
   * Format string for translation using the t() function.
   *
   * @param string $string
   *   A translatable string.
   *
   * @return string
   *   A translatable string formatted for translation using the t() function.
   */
  protected function formatStringTranslation(string $string): string {
    if (str_contains($string, '"') && str_contains($string, "'")) {
      return "t('" . str_replace("'", "\'", $string) . "');";
    }
    elseif (str_contains($string, '"')) {
      return "t('" . $string . "');";
    }
    else {
      return 't("' . $string . '");';
    }
  }

  /**
   * Import Schema.org types and properties tables.
   */
  public function importTables(): void {
    $this->importTable('types');
    $this->importTable('properties');
  }

  /**
   * Installs and populates Schema.org table.
   *
   * @param string $name
   *   The Schema.org table type (properties or types).
   */
  protected function importTable(string $name): void {
    $table = 'schemadotorg_' . $name;
    $filename = __DIR__ . '/../data/' . static::VERSION . '/schemaorg-current-https-' . $name . '.csv';

    // Truncate table.
    $this->database->truncate($table)->execute();

    // Load CSV.
    $handle = fopen($filename, 'r');

    // Get field names.
    $fields = fgetcsv($handle);
    array_walk($fields, function (&$field_name): void {
      $field_name = $this->schemaNames->camelCaseToSnakeCase($field_name);
    });

    // Insert multiple records.
    $query = $this->database->insert($table)->fields($fields);
    while ($row = fgetcsv($handle)) {
      $values = [];
      foreach ($fields as $index => $field_name) {
        $values[$field_name] = $row[$index] ?? '';
      }
      $query->values($values);
    }
    $query->execute();
  }

  /**
   * Reinstall Schema.org tables.
   */
  protected function reinstallSchema(): void {
    $tables = $this->schema();
    foreach ($tables as $name => $table) {
      if ($this->database->schema()->tableExists($name)) {
        $this->database->schema()->dropTable($name);
      }
      $this->database->schema()->createTable($name, $table);
    }
  }

}
