<?php

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the custom field type.
 *
 * @group custom_field
 */
class CustomFieldItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'custom_field',
    'filter',
  ];

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The CustomFieldTypeManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected $customFieldTypeManager;

  /**
   * The CustomFieldWidgetManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager
   */
  protected $customFieldWidgetManager;

  /**
   * The CustomFieldFormatterManager service.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager
   */
  protected $customFieldFormatterManager;

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle type.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setUp();
    $this->entityType = 'entity_test';
    $this->bundle = 'entity_test';
    $this->fieldName = 'field_test';

    // Get the services required for testing.
    $this->customFieldTypeManager = $this->container->get('plugin.manager.custom_field_type');
    $this->customFieldWidgetManager = $this->container->get('plugin.manager.custom_field_widget');
    $this->customFieldFormatterManager = $this->container->get('plugin.manager.custom_field_formatter');

    $columns = [];
    $widgets = [];

    $default_column = [
      'max_length' => 255,
      'unsigned' => FALSE,
      'precision' => 10,
      'scale' => 2,
      'size' => 'normal',
      'datetime_type' => 'datetime',
    ];

    $data_types = array_keys($this->customFieldTypeManager->dataTypes());
    foreach ($data_types as $delta => $data_type) {
      /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
      $plugin = $this->customFieldTypeManager->createInstance($data_type);
      $default_widget = $plugin->getPluginDefinition()['default_widget'] ?? NULL;
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager $widget_plugin */
      $widget_plugin = $this->customFieldWidgetManager->createInstance($default_widget);

      // Set the columns.
      $columns[$data_type] = [
        'name' => $data_type,
        'type' => $data_type,
      ] + $default_column;

      // Add a big integer column.
      $columns['integer_big'] = [
        'name' => 'integer_big',
        'type' => 'integer',
      ] + $default_column;
      $columns['integer_big']['size'] = 'big';

      // Set the widget settings for the fields.
      $widget_settings = $plugin->getWidgetSetting('settings');
      if (method_exists($widget_plugin, 'defaultWidgetSettings')) {
        $widget_settings += $widget_plugin->defaultWidgetSettings()['settings'];
      }
      if (isset($widget_settings['min'])) {
        $widget_settings['min'] = 1;
      }
      if (isset($widget_settings['max'])) {
        $widget_settings['max'] = 100;
      }
      $widgets[$data_type] = [
        'type' => $default_widget,
        'weight' => $delta,
        'widget_settings' => [
          'label' => ucfirst(str_replace(['-', '_'], ' ', $data_type)),
          'settings' => $widget_settings,
        ],
      ];
    }

    // Create a generic custom field for validation.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'type' => 'custom',
      'settings' => [
        'columns' => $columns,
      ],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'entity_type' => $this->entityType,
      'field_name' => $this->fieldName,
      'bundle' => $this->bundle,
      'settings' => [
        'field_settings' => $widgets,
      ],
    ]);
    $this->field->save();
  }

  /**
   * Tests using entity fields of the custom field type.
   */
  public function testCustomFieldItem() {
    $random = new Random();
    $expected = [
      'uuid' => [
        'widget' => [
          'id' => 'uuid',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\UuidWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'string' => [
        'widget' => [
          'id' => 'text',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TextWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'map' => [
        'widget' => [
          'id' => 'map_key_value',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\MapKeyValueWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'color' => [
        'widget' => [
          'id' => 'color',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\ColorWidget',
        ],
        'formatter' => [
          'id' => 'string',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\StringFormatter',
        ],
      ],
      'float' => [
        'widget' => [
          'id' => 'float',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\FloatWidget',
        ],
        'formatter' => [
          'id' => 'number_decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DecimalFormatter',
        ],
      ],
      'integer' => [
        'widget' => [
          'id' => 'integer',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\IntegerWidget',
        ],
        'formatter' => [
          'id' => 'number_integer',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\IntegerFormatter',
        ],
      ],
      'string_long' => [
        'widget' => [
          'id' => 'textarea',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TextareaWidget',
        ],
        'formatter' => [
          'id' => 'text_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\TextDefaultFormatter',
        ],
      ],
      'uri' => [
        'widget' => [
          'id' => 'url',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\UrlWidget',
        ],
        'formatter' => [
          'id' => 'uri_link',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\UriLinkFormatter',
        ],
      ],
      'boolean' => [
        'widget' => [
          'id' => 'checkbox',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\CheckboxWidget',
        ],
        'formatter' => [
          'id' => 'boolean',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\BooleanFormatter',
        ],
      ],
      'email' => [
        'widget' => [
          'id' => 'email',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\EmailWidget',
        ],
        'formatter' => [
          'id' => 'email_mailto',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\MailToFormatter',
        ],
      ],
      'decimal' => [
        'widget' => [
          'id' => 'decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\DecimalWidget',
        ],
        'formatter' => [
          'id' => 'number_decimal',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DecimalFormatter',
        ],
      ],
      'telephone' => [
        'widget' => [
          'id' => 'telephone',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\TelephoneWidget',
        ],
        'formatter' => [
          'id' => 'telephone_link',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\TelephoneLinkFormatter',
        ],
      ],
      'datetime' => [
        'widget' => [
          'id' => 'datetime_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldWidget\DateTimeDefaultWidget',
        ],
        'formatter' => [
          'id' => 'datetime_default',
          'class' => 'Drupal\custom_field\Plugin\CustomField\FieldFormatter\DateTimeDefaultFormatter',
        ],
      ],
    ];

    // Perform assertions to verify that the storage was added successfully.
    $fieldStorageConfig = FieldStorageConfig::loadByName($this->entityType, $this->fieldName);
    $this->assertNotNull($fieldStorageConfig, 'The field storage configuration exists.');
    $columns = $fieldStorageConfig->getSetting('columns');
    foreach ($columns as $column) {
      $type = $column['type'];
      /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $plugin */
      $field_type = $this->customFieldTypeManager->createInstance($type);
      $plugin = $field_type->getPluginDefinition();

      // Assert the expected default widget id for the field type plugin.
      $default_widget = $plugin['default_widget'] ?? NULL;
      $this->assertEquals($default_widget, $expected[$type]['widget']['id'], 'The default widget id is equal to the expected widget id.');

      // Assert the expected default widget class for the field type plugin.
      /** @var \Drupal\custom_field\Plugin\CustomFieldWidgetManager $widget_plugin */
      $widget_plugin = $this->customFieldWidgetManager->createInstance($default_widget);
      $this->assertEquals(get_class($widget_plugin), $expected[$type]['widget']['class'], 'The default widget class is equal to the expected widget class.');

      // Assert the expected default formatter id for the field type plugin.
      $default_formatter = $plugin['default_formatter'] ?? NULL;
      $this->assertEquals($default_formatter, $expected[$type]['formatter']['id'], 'The default formatter is equal to the expected formatter.');

      // Assert the expected default formatter class for the field type plugin.
      /** @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager $formatter_plugin */
      $formatter_plugin = $this->customFieldFormatterManager->createInstance($default_formatter);
      $this->assertEquals(get_class($formatter_plugin), $expected[$type]['formatter']['class'], 'The default formatter class is equal to the expected formatter class.');
    }

    // Create an entity.
    $entity = EntityTest::create();
    $string_long = $random->paragraphs(4);
    $float = 3.14;
    $email = 'test@example.com';
    $telephone = '+0123456789';
    $uri_external = 'https://www.drupal.com';
    $boolean = '1';
    $color = '#000000';
    $datetime = '2014-01-01T20:00:00';
    $map = [
      [
        'key' => 'Key1',
        'value' => 'Value1',
      ],
      [
        'key' => 'Key2',
        'value' => 'Value2',
      ],
    ];
    // Test string constraints.
    $entity->{$this->fieldName}->string = $this->randomString(256);
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'String exceeding length causes validation error');
    $string = $this->randomString(255);
    $entity->{$this->fieldName}->string = $string;

    // Test integer constraints.
    $integer_max = 2147483647;
    $integer_max_big = 9223372036854775807;
    $integer_min = -2147483648;
    $entity->{$this->fieldName}->integer = $integer_max + 1;
    // Test integer field with 'big' column size.
    $entity->{$this->fieldName}->integer_big = $integer_max_big;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'The integer value is exceeds max.');
    $entity->{$this->fieldName}->integer = $integer_min - 1;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'The integer value is below min.');
    $integer = rand(0, 10);
    $entity->{$this->fieldName}->integer = $integer;

    // Test decimal constraints.
    $entity->{$this->fieldName}->decimal = '20-40';
    $this->assertCount(1, $violations, 'Wrong decimal value causes validation error');
    $entity->{$this->fieldName}->decimal = -1;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'Decimal min value causes validation error');
    $entity->{$this->fieldName}->decimal = 101.50;
    $violations = $entity->validate();
    $this->assertCount(1, $violations, 'Decimal max value causes validation error');
    $decimal = 31.30;
    $entity->{$this->fieldName}->decimal = $decimal;
    $entity->{$this->fieldName}->float = $float;
    $entity->{$this->fieldName}->email = $email;
    $entity->{$this->fieldName}->telephone = $telephone;
    $entity->{$this->fieldName}->uri = $uri_external;
    $entity->{$this->fieldName}->boolean = $boolean;
    $entity->{$this->fieldName}->color = $color;
    $entity->{$this->fieldName}->string_long = $string_long;
    $entity->{$this->fieldName}->map = $map;
    $entity->{$this->fieldName}->datetime = $datetime;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->{$this->fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $entity->{$this->fieldName}[0]);
    $this->assertEquals($string, $entity->{$this->fieldName}->string);
    $this->assertEquals($string, $entity->{$this->fieldName}[0]->string);
    $this->assertEquals(strlen($string_long), strlen($entity->{$this->fieldName}->string_long));
    $this->assertEquals(strlen($string_long), strlen($entity->{$this->fieldName}[0]->string_long));
    $this->assertEquals($integer, $entity->{$this->fieldName}->integer);
    $this->assertEquals($integer, $entity->{$this->fieldName}[0]->integer);
    $this->assertEquals($integer_max_big, $entity->{$this->fieldName}->integer_big);
    $this->assertEquals($integer_max_big, $entity->{$this->fieldName}[0]->integer_big);
    $this->assertEquals((float) $decimal, $entity->{$this->fieldName}->decimal);
    $this->assertEquals((float) $decimal, $entity->{$this->fieldName}[0]->decimal);
    $this->assertEquals($float, $entity->{$this->fieldName}->float);
    $this->assertEquals($float, $entity->{$this->fieldName}[0]->float);
    $this->assertEquals($email, $entity->{$this->fieldName}->email);
    $this->assertEquals($email, $entity->{$this->fieldName}[0]->email);
    $this->assertEquals($telephone, $entity->{$this->fieldName}->telephone);
    $this->assertEquals($telephone, $entity->{$this->fieldName}[0]->telephone);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}->uri);
    $this->assertEquals($uri_external, $entity->{$this->fieldName}[0]->uri);
    $this->assertEquals($boolean, $entity->{$this->fieldName}->boolean);
    $this->assertEquals($boolean, $entity->{$this->fieldName}[0]->boolean);
    $this->assertEquals($color, $entity->{$this->fieldName}->color);
    $this->assertEquals($color, $entity->{$this->fieldName}[0]->color);
    $this->assertEquals($map, $entity->{$this->fieldName}->map);
    $this->assertEquals($map, $entity->{$this->fieldName}[0]->map);
    $this->assertEquals($datetime, $entity->{$this->fieldName}->datetime);
    $this->assertEquals($datetime, $entity->{$this->fieldName}[0]->datetime);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime']->getDateTime()->getTimeZone()->getName());

    // Verify changing the field values.
    $new_string = $this->randomString(255);
    $new_string_long = $random->paragraphs(6);
    $new_integer = rand(11, 20);
    $new_float = rand(1001, 2000) / 100;
    $new_decimal = 18.20;
    $new_email = 'test2@example.com';
    $new_telephone = '+41' . rand(1000000, 9999999);
    $new_uri_external = 'https://www.drupal.org';
    $new_boolean = 0;
    $new_color = '#FFFFFF';
    $new_datetime = '2016-11-04T00:21:00';
    $new_map = [
      [
        'key' => 'New Key1',
        'value' => 'New Value1',
      ],
      [
        'key' => 'New Key2',
        'value' => 'New Value2',
      ],
      [
        'key' => 'New Key3',
        'value' => 'New Value3',
      ],
    ];
    $entity->{$this->fieldName}->string = $new_string;
    $this->assertEquals($new_string, $entity->{$this->fieldName}->string);
    $entity->{$this->fieldName}->integer = $new_integer;
    $this->assertEquals($new_integer, $entity->{$this->fieldName}->integer);
    $entity->{$this->fieldName}->decimal = $new_decimal;
    $this->assertEquals($new_decimal, $entity->{$this->fieldName}->decimal);
    $entity->{$this->fieldName}->float = $new_float;
    $this->assertEquals($new_float, $entity->{$this->fieldName}->float);
    $entity->{$this->fieldName}->email = $new_email;
    $this->assertEquals($new_email, $entity->{$this->fieldName}->email);
    $entity->{$this->fieldName}->telephone = $new_telephone;
    $this->assertEquals($new_telephone, $entity->{$this->fieldName}->telephone);
    $entity->{$this->fieldName}->uri = $new_uri_external;
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}->uri);
    $entity->{$this->fieldName}->boolean = $new_boolean;
    $this->assertEquals($new_boolean, $entity->{$this->fieldName}->boolean);
    $entity->{$this->fieldName}->color = $new_color;
    $this->assertEquals($new_color, $entity->{$this->fieldName}->color);
    $entity->{$this->fieldName}->string_long = $new_string_long;
    $this->assertEquals(strlen($new_string_long), strlen($entity->{$this->fieldName}[0]->string_long));
    $entity->{$this->fieldName}->map = $new_map;
    $this->assertEquals($new_map, $entity->{$this->fieldName}[0]->map);
    $entity->{$this->fieldName}->datetime = $new_datetime;
    $this->assertEquals($new_datetime, $entity->{$this->fieldName}[0]->datetime);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime']->getDateTime()->getTimeZone()->getName());

    // Read changed entity and assert changed values.
    $this->entityValidateAndSave($entity);
    $entity = EntityTest::load($id);
    $this->assertEquals($new_string, $entity->{$this->fieldName}->string);
    $this->assertEquals($new_integer, $entity->{$this->fieldName}->integer);
    $this->assertEquals($new_decimal, $entity->{$this->fieldName}->decimal);
    $this->assertEquals($new_float, $entity->{$this->fieldName}->float);
    $this->assertEquals($new_email, $entity->{$this->fieldName}->email);
    $this->assertEquals($new_telephone, $entity->{$this->fieldName}->telephone);
    $this->assertEquals($new_uri_external, $entity->{$this->fieldName}->uri);
    $this->assertEquals($new_boolean, $entity->{$this->fieldName}->boolean);
    $this->assertEquals($new_color, $entity->{$this->fieldName}->color);
    $this->assertEquals(strlen($new_string_long), strlen($entity->{$this->fieldName}[0]->string_long));
    $this->assertEquals($new_map, $entity->{$this->fieldName}[0]->map);
    $this->assertEquals($new_datetime, $entity->{$this->fieldName}[0]->datetime);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime']->getDateTime()->getTimeZone()->getName());

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Tests using the datetime_type of 'date'.
   */
  public function testDateOnly() {
    $columns = $this->fieldStorage->getSetting('columns');
    $columns['datetime']['datetime_type'] = 'date';
    $this->fieldStorage->setSetting('columns', $columns);
    $this->fieldStorage->save();

    // Verify entity creation.
    $entity = EntityTest::create();
    $date = '2014-01-01';
    $entity->{$this->fieldName}->datetime = $date;
    $entity->name->value = $this->randomMachineName();
    $this->entityValidateAndSave($entity);

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->{$this->fieldName});
    $this->assertInstanceOf(FieldItemInterface::class, $entity->{$this->fieldName}[0]);
    $this->assertEquals($date, $entity->{$this->fieldName}->datetime);
    $this->assertEquals($date, $entity->{$this->fieldName}[0]->datetime);
    $this->assertEquals(CustomFieldTypeInterface::STORAGE_TIMEZONE, $entity->{$this->fieldName}[0]->getProperties()['datetime']->getDateTime()->getTimeZone()->getName());
    /** @var \Drupal\Core\Datetime\DrupalDateTime $date_object */
    $date_object = $entity->{$this->fieldName}[0]->getProperties()['datetime']->getDateTime();
    $this->assertEquals('00:00:00', $date_object->format('H:i:s'));
    $date_object->setDefaultDateTime();
    $this->assertEquals('12:00:00', $date_object->format('H:i:s'));
  }

}
