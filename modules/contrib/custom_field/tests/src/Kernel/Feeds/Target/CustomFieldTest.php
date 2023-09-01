<?php

namespace Drupal\Tests\custom_field\Kernel\Feeds\Target;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;

/**
 * Tests for mapping to custom_field fields.
 *
 * @group custom_field
 */
class CustomFieldTest extends FeedsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    'custom_field',
    'custom_field_test',
    'feeds',
    'system',
  ];

  /**
   * The feed type to test with.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * The CustomFieldTypeManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The entity type for testing.
   *
   * @var string
   */
  protected string $entityTypeId;

  /**
   * The field name for testing.
   *
   * @var string
   */
  protected string $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Define the entity type and field names from the provided configuration.
    $this->entityTypeId = 'node';
    $bundle = 'custom_field_entity_test';
    $this->fieldName = 'field_custom_field_test';

    $this->installEntitySchema('node');
    $this->installConfig(['custom_field', 'custom_field_test']);

    // Get the services required for testing.
    $this->customFieldTypeManager = $this->container->get('plugin.manager.customfield_type');
    $fieldStorageConfig = FieldStorageConfig::loadByName($this->entityTypeId, $this->fieldName);
    $columns = $fieldStorageConfig->getSetting('columns');

    // Create and configure feed type.
    $sources = [
      'title' => 'title',
    ];

    $mappings = [
      [
        'target' => 'title',
        'map' => ['value' => 'title'],
      ],
    ];

    $custom_field_map = [
      'target' => $this->fieldName,
      'map' => [],
    ];

    foreach ($columns as $column) {
      $sources[$column['name']] = $column['name'];
      $custom_field_map['map'][$column['name']] = $column['name'];
    }

    $mappings[] = $custom_field_map;

    $this->feedType = $this->createFeedTypeForCsv(
      $sources,
      [
        'mappings' => $mappings,
        'processor_configuration' => [
          'authorize' => FALSE,
          'values' => [
            'type' => $bundle,
          ],
        ],
      ],
    );
  }

  /**
   * Basic test loading a CSV file.
   */
  public function test() {
    // Import CSV file.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(3);
    $fieldStorageConfig = FieldStorageConfig::loadByName($this->entityTypeId, $this->fieldName);
    $expected_values = [
      1 => [
        'string_test' => 'String 1',
        'string_long_test' => 'Long string 1',
        'integer_test' => 42,
        'decimal_test' => '3.14',
        'float_test' => '2.718',
        'email_test' => 'test@example.com',
        'telephone_test' => '+1234567890',
        'uri_test' => 'http://www.example.com',
        'boolean_test' => '1',
        'uuid_test' => '550e8400-e29b-41d4-a716-446655440000',
        'color_test' => '#FFA500',
        'map_test' => [
          'key1' => 'value1',
          'key2' => 'value2',
        ],
      ],
      2 => [
        'string_test' => 'String 2',
        'string_long_test' => 'Long string 2',
        'integer_test' => NULL,
        'decimal_test' => '-1.62',
        'float_test' => '0.5778',
        'email_test' => NULL,
        'telephone_test' => '-9876543210',
        'uri_test' => 'https://www.example.org',
        'boolean_test' => '1',
        'uuid_test' => '123e4567-e89b-12d3-a456-556642440000',
        'color_test' => NULL,
        'map_test' => NULL,
      ],
      3 => [
        'string_test' => 'String 3',
        'string_long_test' => '',
        'integer_test' => '1234',
        'decimal_test' => '1.62',
        'float_test' => '0.577',
        'email_test' => NULL,
        'telephone_test' => NULL,
        'uri_test' => 'https://www.example.com',
        'boolean_test' => '1',
        'uuid_test' => '123e4567-e89b-12d3-a456-556642440001',
        'color_test' => '#FFFFFF',
        'map_test' => NULL,
      ],
    ];
    foreach ($expected_values as $nid => $data) {
      $node = Node::load($nid);
      $field_values = $node->get($this->fieldName)->getValue();
      $this->assertNotEmpty($field_values, 'The field value is not empty');
      foreach ($field_values as $field_value) {
        foreach ($field_value as $column_name => $value) {
          $data_value = $data[(string) $column_name];
          $this->assertEquals($value, $data_value, 'The real value is equal to expected value.');
        }
      }
    }
    // Check if mappings can be unique.
    $unique_types = [
      'string_test',
      'string_long_test',
      'integer_test',
      'decimal_test',
      'email_test',
      'uri_test',
      'telephone_test',
    ];
    $unique_count = count($unique_types);
    $mappings = $this->feedType->getMappings();
    $mappings[1]['unique'] = $unique_types;
    $this->feedType->setMappings($mappings);
    $this->feedType->save();
    $updated_mappings = $this->feedType->getMappings();
    $this->assertCount($unique_count, $updated_mappings[1]['unique'], 'The count of expected unique types is accurate.');
  }

  /**
   * Test a CSV file with non-existent values.
   */
  public function testNonExistent() {
    // Import CSV file with non-existent values.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content_non_existent.csv',
    ]);
    $feed->import();
    $this->assertNodeCount(2);
    $node_ids = [1, 2];
    foreach ($node_ids as $node_id) {
      $node = Node::load($node_id);
      $values = $node->get($this->fieldName)->getValue();
      $this->assertEmpty($values, 'The field value is empty');
    }
  }

  /**
   * Overrides the absolute directory path of the Feeds module.
   *
   * @return string
   *   The absolute path to the custom_field module.
   */
  protected function absolutePath(): string {
    return $this->absolute() . '/' . $this->getModulePath('custom_field');
  }

}
