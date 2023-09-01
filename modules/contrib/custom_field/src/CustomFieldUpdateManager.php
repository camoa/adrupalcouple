<?php

namespace Drupal\custom_field;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * My custom service class.
 */
class CustomFieldUpdateManager implements CustomFieldUpdateManagerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;
  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The plugin manager for custom field types.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The installed entity definition repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $lastInstalledSchemaRepository;

  /**
   * Constructs a new CustomFieldUpdateManager object.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_type_manager
   *   The plugin manager for custom field types.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository
   *   The installed entity definition repository.
   */
  public function __construct(
    EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    Connection $database,
    CustomFieldTypeManagerInterface $custom_field_type_manager,
    EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository
  ) {
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->database = $database;
    $this->customFieldTypeManager = $custom_field_type_manager;
    $this->lastInstalledSchemaRepository = $last_installed_schema_repository;
  }

  /**
   * Adds a new column to the specified field storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $new_property
   *   The new property name (column name).
   * @param string $data_type
   *   The data type to add. Allowed values such as: "integer", "boolean" etc.
   * @param array $options
   *   An array of options to set for new column.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function addColumn(string $entity_type_id, string $field_name, string $new_property, string $data_type, array $options = []): void {
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id);

    // Return early if no storage definition.
    if (!$field_storage_definition) {
      $message = $this->t('There is no field storage definition for field @field_name and entity type @type.', [
        '@field_name' => $field_name,
        '@type' => $entity_type_id,
      ]);
      throw new \Exception($message);
    }

    $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $data_types = $this->customFieldTypeManager->dataTypes();
    $column = array_key_exists($data_type, $data_types) ? $this->customFieldTypeManager->dataTypes()[$data_type] : NULL;

    // If we don't have a matching data type, return early.
    if (!$column) {
      $allowed_data_types = implode(', ', array_keys($this->customFieldTypeManager->dataTypes()));
      $message = $this->t('@data_type is an invalid data type. Use one of these: [@data_types]', [
        '@data_type' => $data_type,
        '@data_types' => $allowed_data_types,
      ]);
      throw new \Exception($message);
    }
    $spec = $column['schema'];
    $spec['not null'] = FALSE;
    $spec['default'] = NULL;

    // Match keys from options to schema.
    $option_matches = array_intersect_key($options, $spec);

    foreach ($option_matches as $type => $option) {
      switch ($type) {
        case 'precision':
          if (is_numeric($option) && $option <= 65) {
            $spec[$type] = (int) $option;
          }
          break;

        case 'scale':
          if (is_numeric($option) && $option <= 30) {
            $spec[$type] = (int) $option;
          }
          break;

        case 'length':
          if ($data_type === 'string' && is_numeric($option) && ($option < 255)) {
            $spec[$type] = (int) $option;
          }
          break;

        case 'unsigned':
          if (is_bool($option)) {
            $spec[$type] = $option;
          }
          break;
      }
    }

    // If the storage is SqlContentEntityStorage, update the database schema.
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $storage->getTableMapping([
      $field_name => $field_storage_definition,
    ]);
    $table_names = $table_mapping->getDedicatedTableNames();
    $column_name = "{$field_storage_definition->getName()}_{$new_property}";
    $schema = $this->database->schema();

    $existing_data = [];
    foreach ($table_names as $table_name) {
      $field_exists = $schema->fieldExists($table_name, $column_name);
      $table_exists = $schema->tableExists($table_name);
      // Add the new column.
      if (!$field_exists && $table_exists) {
        $schema->addField($table_name, $column_name, $spec);
        // Get the old data.
        $existing_data[$table_name] = $this->database->select($table_name)
          ->fields($table_name)
          ->execute()
          ->fetchAll(\PDO::FETCH_ASSOC);
        // Wipe it.
        $this->database->truncate($table_name)->execute();
      }
      else {
        // Show message that field already exists.
        throw new \Exception($this->t('The column @column already exists in table @table', [
          '@column' => $column_name,
          '@table' => $table_name,
        ]));
      }
    }

    // Load the installed field schema so that it can be updated.
    $schema_key = "$entity_type_id.field_schema_data.$field_name";
    $field_schema_data = $entity_storage_schema_sql->get($schema_key);

    // Add the new column to the installed field schema.
    foreach ($field_schema_data as $table_name => $fieldSchema) {
      $field_schema_data[$table_name]['fields'][$column_name] = $spec;
    }

    // Save changes to the installed field schema.
    $entity_storage_schema_sql->set($schema_key, $field_schema_data);

    // Update cached entity definitions for entity types.
    if ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
      $definitions = $this->lastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
      $definitions[$field_name] = $field_storage_definition;
      $this->lastInstalledSchemaRepository->setLastInstalledFieldStorageDefinitions($entity_type_id, $definitions);
    }

    // Update config.
    $field_storage_config = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    $columns = $field_storage_config->getSetting('columns');

    $columns[$new_property] = [
      'type' => $data_type,
      'name' => $new_property,
      'max_length' => $spec['length'] ?? NULL,
      'unsigned' => $spec['unsigned'] ?? FALSE,
      'precision' => $spec['precision'] ?? NULL,
      'scale' => $spec['scale'] ?? NULL,
    ];

    $field_storage_config->setSetting('columns', $columns);

    $field_storage_config->save();

    if (!empty($existing_data)) {
      $this->restoreData($table_names, $existing_data);
    }

  }

  /**
   * Removes a column from the specified field storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string $property
   *   The name of the column to remove.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
   * @throws \Exception
   */
  public function removeColumn(string $entity_type_id, string $field_name, string $property): void {
    $field_storage_definition = $this->entityDefinitionUpdateManager->getFieldStorageDefinition($field_name, $entity_type_id);

    // Return early if no storage definition.
    if (!$field_storage_definition) {
      $message = $this->t('There is no field storage definition for field @field_name and entity type @type.', [
        '@field_name' => $field_name,
        '@type' => $entity_type_id,
      ]);
      throw new \Exception($message);
    }

    $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
    $schema = $this->database->schema();
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $entity_storage->getTableMapping([
      $field_name => $field_storage_definition,
    ]);
    $table_names = $table_mapping->getDedicatedTableNames();
    $table = $table_mapping->getDedicatedDataTableName($field_storage_definition);
    $column_name = $table_mapping->getFieldColumnName($field_storage_definition, $property);
    $table_columns = $field_storage_definition->getColumns();

    // Return early if there's only one column or if $property doesn't exist.
    if (!isset($table_columns[$property])) {
      $message = $this->t("@column can't be removed because it doesn't exist for @field", [
        '@column' => $column_name,
        '@field' => $field_name,
      ]);
      throw new \Exception($message);
    }
    elseif (count($table_columns) <= 1) {
      $message = $this->t('Removing column @column would leave no remaining columns. The custom field requires at least 1 column.', ['@column' => $column_name]);
      throw new \Exception($message);
    }

    // Load the installed field schema so that it can be updated.
    $schema_key = "$entity_type_id.field_schema_data.$field_name";
    $field_schema_data = $entity_storage_schema_sql->get($schema_key);

    // Save changes to the installed field schema.
    if ($field_schema_data) {
      $existing_data = [];
      foreach ($table_names as $table_name) {
        $field_exists = $schema->fieldExists($table_name, $column_name);
        $table_exists = $schema->tableExists($table_name);
        // Remove the new column.
        if ($field_exists && $table_exists) {
          // Get the old data.
          $existing_data[$table_name] = $this->database->select($table_name)
            ->fields($table_name)
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
          // Wipe it.
          $this->database->truncate($table_name)->execute();
          unset($field_schema_data[$table_name]['fields'][$column_name]);
        }
      }
      // Update schema definition in database.
      $entity_storage_schema_sql->set($schema_key, $field_schema_data);
      // Try to drop field data.
      $this->database->schema()->dropField($table, $column_name);
    }

    // Update the field storage config.
    $field_storage_config = FieldStorageConfig::loadByName($entity_type_id, $field_name);
    // Remove the column from the field storage configuration.
    $columns = $field_storage_config->getSetting('columns');
    if (isset($columns[$property])) {
      unset($columns[$property]);
      $field_storage_config->setSetting('columns', $columns);
      $field_storage_config->save();
    }

    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
    foreach ($bundles as $bundle) {
      // Update the field config for each bundle.
      if ($field_config = FieldConfig::loadByName($entity_type_id, $bundle, $field_name)) {
        $settings = $field_config->getSettings();
        foreach ($settings as $setting_type => $setting) {
          if (is_array($setting) && isset($setting[$property])) {
            unset($settings[$setting_type][$property]);
            $field_config->setSettings($settings);
            $field_config->save();
          }
        }

        // Update entity form display configs.
        if ($displays = $this->entityTypeManager->getStorage('entity_form_display')->loadByProperties([
          'targetEntityType' => $field_config->getTargetEntityTypeId(),
          'bundle' => $field_config->getTargetBundle(),
        ])) {
          /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
          foreach ($displays as $display) {
            if ($component = $display->getComponent($field_name)) {
              // Check for settings to remove in the custom_default plugin.
              if (isset($component['settings']['proportions'][$property])) {
                unset($component['settings']['proportions'][$property]);
                $display->setComponent($field_name, $component)->save();
              }
              // Check for settings to remove in the custom_flex plugin.
              if (isset($component['settings']['columns'][$property])) {
                unset($component['settings']['columns'][$property]);
                $display->setComponent($field_name, $component)->save();
              }
            }
          }
        }

        // Update entity view display configs.
        if ($displays = $this->entityTypeManager->getStorage('entity_view_display')->loadByProperties([
          'targetEntityType' => $field_config->getTargetEntityTypeId(),
          'bundle' => $field_config->getTargetBundle(),
        ])) {
          /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
          foreach ($displays as $display) {
            if ($component = $display->getComponent($field_name)) {
              if (isset($component['settings']['label_display'][$property])) {
                unset($component['settings']['label_display'][$property]);
                $display->setComponent($field_name, $component)->save();
              }
            }
          }
        }
      }
    }

    // Restore the data after removing the column.
    foreach ($existing_data as $table => $fields) {
      foreach ($fields as $key => $field) {
        unset($existing_data[$table][$key][$column_name]);
      }
    }

    if (!empty($existing_data)) {
      $this->restoreData($table_names, $existing_data);
    }
  }

  /**
   * A batch wrapper function for restoring data.
   *
   * @param array $tables
   *   The array of table names to restore data for.
   * @param array $existing_data
   *   The existing data to be restored for each table.
   */
  private function restoreData(array $tables, array $existing_data): void {
    $batch_size = 50;

    // Initialize the batch.
    $batch = [
      'title' => $this->t('Restoring data...'),
      'operations' => [],
      'init_message' => $this->t('Starting data restoration for @count tables...', ['@count' => count($tables)]),
      'error_message' => $this->t('An error occurred during data restoration. Please check the logs for errors.'),
      'finished' => [$this, 'restoreDataBatchFinished'],
    ];

    // Add table names to the batch context.
    $batch['context']['tables'] = $tables;

    // Process each table separately and create a batch for each one.
    foreach ($tables as $table_name) {
      if (!empty($existing_data[$table_name])) {
        // Populate the 'operations' array with data processing tasks.
        $total_rows = count($existing_data[$table_name]);
        $chunks = array_chunk($existing_data[$table_name], $batch_size);
        $chunk_total = count($chunks);

        foreach ($chunks as $chunk_index => $chunk) {
          $batch['operations'][] = [
            [$this, 'restoreDataBatchCallback'],
            [$table_name, $chunk, $total_rows, $chunk_total, $chunk_index + 1],
          ];
        }
      }
    }
    if (!empty($batch['operations'])) {
      // Queue the batch for processing.
      batch_set($batch);
    }
  }

  /**
   * The batch processing callback function for restoring data.
   *
   * @param string $table_name
   *   The table to batch process.
   * @param array $data
   *   The array of data to insert into the table.
   * @param int $total_rows
   *   The total number of rows in the table being processed.
   * @param int $chunk_total
   *   The total number of chunks for the table being processed.
   * @param int $chunk_index
   *   The index of the current chunk being processed (1-based).
   * @param array|\ArrayAccess $context
   *   The context array.
   */
  public static function restoreDataBatchCallback(string $table_name, array $data, int $total_rows, int $chunk_total, int $chunk_index, mixed &$context): void {
    // Initialize 'progress' key if it does not exist.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }

    if (!isset($context['sandbox']['table'])) {
      $context['sandbox']['table'] = $table_name;
    }

    // Initialize 'processed_rows' key for the table if it does not exist.
    if (!isset($context['results'][$table_name]['processed_rows'])) {
      $context['results'][$table_name]['processed_rows'] = 0;
    }

    $fields = array_keys($data[0]);
    $insert_query = \Drupal::database()->insert($table_name)->fields($fields);

    // Process a batch of rows for the current chunk.
    $batch_size = 50;
    $start = $context['sandbox']['progress'];
    $total_rows_chunk = count($data);
    $rows_to_process = array_slice($data, $start, $batch_size);

    // Use batch insert to optimize insertion.
    foreach ($rows_to_process as $row) {
      $insert_query->values(array_values($row));
      $context['sandbox']['progress']++;
      $context['results'][$table_name]['processed_rows']++;
    }

    // Insert multiple rows in a single query using batch insert.
    $insert_query->execute();

    // Update the progress message to include the table information.
    $context['message'] = t('Processed @current out of @total. (Table: @table, Chunk: @chunk/@total_chunks)', [
      '@current' => $context['sandbox']['progress'],
      '@total' => $total_rows,
      '@table' => $context['sandbox']['table'],
      '@chunk' => $chunk_index,
      '@total_chunks' => $chunk_total,
    ]);

    // Calculate the overall progress for the batch process.
    if ($total_rows_chunk > 0) {
      $context['finished'] = $context['sandbox']['progress'] / $total_rows_chunk;
    }
    else {
      unset($context['sandbox']['table']);
      $context['finished'] = 1;
    }

  }

  /**
   * The batch processing finished callback function.
   *
   * @param bool $success
   *   The end result status of the batching.
   * @param array $results
   *   The results array of the batching.
   */
  public static function restoreDataBatchFinished(bool $success, array $results): void {
    if ($success) {
      foreach ($results as $table_name => $table_results) {
        if (isset($table_results['processed_rows'])) {
          $total_rows = $table_results['processed_rows'];
          $message = t('Updated @total_rows rows in @table', [
            '@table' => $table_name,
            '@total_rows' => $total_rows,
          ]);
          \Drupal::messenger()->addMessage($message, 'status');
        }
      }
    }
    else {
      \Drupal::messenger()->addMessage(\Drupal::translation()->translate('Data restoration failed. Please check the logs for errors.'), 'error');
    }
  }

}