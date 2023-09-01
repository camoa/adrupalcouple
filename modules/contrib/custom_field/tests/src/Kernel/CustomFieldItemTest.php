<?php

namespace Drupal\Tests\custom_field\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
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
  protected static $modules = ['custom_field'];

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setUp();

    $data_types = [
      'string',
      'string_long',
      'integer',
      'decimal',
      'float',
      'email',
      'telephone',
      'uri',
      'boolean',
      'color',
    ];

    $default_column = [
      'max_length' => 255,
      'unsigned' => FALSE,
      'precision' => 10,
      'scale' => 2,
    ];

    $default_widget_settings = [
      'description' => '',
      'description_display' => 'after',
    ];

    $numeric_widget_settings = [
      'min' => 0,
      'max' => 100,
      'scale' => 2,
    ];

    $columns = [];
    $widgets = [];
    foreach ($data_types as $delta => $type) {
      $columns[$type] = [
        'name' => $type,
        'type' => $type,
      ] + $default_column;

      $widgets[$type] = [
        'type' => $type,
        'weight' => $delta,
        'widget_settings' => [
          'label' => $type,
          'settings' => $default_widget_settings,
        ],
      ];

      switch ($type) {
        case 'string':
          $widgets[$type]['type'] = 'text';
          break;

        case 'integer':
        case 'decimal':
        case 'float':
          $widgets[$type]['widget_settings']['settings'] += $numeric_widget_settings;
          break;

        case 'uri':
          $widgets[$type]['type'] = 'url';
          break;

        case 'boolean':
          $widgets[$type]['type'] = 'checkbox';
          break;
      }
    }

    // Create a generic custom field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'custom',
      'settings' => [
        'columns' => $columns,
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => [
        'field_settings' => $widgets,
      ],
    ])->save();
  }

  /**
   * Tests using entity fields of the custom field type.
   */
  public function testCustomFieldItem() {
    $random = new Random();
    $entity = EntityTest::create();
    $string = $this->randomString(255);
    $string_long = $random->paragraphs(4);
    $integer = rand(0, 10);
    $decimal = '31.3';
    $float = 3.14;
    $email = 'test@example.com';
    $telephone = '+0123456789';
    $uri_external = 'https://www.drupal.com';
    $boolean = '1';
    $color = '#000000';
    $entity->field_test->string = $string;
    $entity->field_test->integer = $integer;
    $entity->field_test->float = $float;
    $entity->field_test->email = $email;
    $entity->field_test->telephone = $telephone;
    $entity->field_test->uri = $uri_external;
    $entity->field_test->boolean = $boolean;
    $entity->field_test->color = $color;
    $entity->field_test->string_long = $string_long;
    $entity->field_test->decimal = $decimal;
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test[0]);
    $this->assertEquals($string, $entity->field_test->string);
    $this->assertEquals($string, $entity->field_test[0]->string);
    $this->assertEquals(strlen($string_long), strlen($entity->field_test->string_long));
    $this->assertEquals(strlen($string_long), strlen($entity->field_test[0]->string_long));
    $this->assertEquals($integer, $entity->field_test->integer);
    $this->assertEquals($integer, $entity->field_test[0]->integer);
    $this->assertEquals((float) $decimal, $entity->field_test->decimal);
    $this->assertEquals((float) $decimal, $entity->field_test[0]->decimal);
    $this->assertEquals($float, $entity->field_test->float);
    $this->assertEquals($float, $entity->field_test[0]->float);
    $this->assertEquals($email, $entity->field_test->email);
    $this->assertEquals($email, $entity->field_test[0]->email);
    $this->assertEquals($telephone, $entity->field_test->telephone);
    $this->assertEquals($telephone, $entity->field_test[0]->telephone);
    $this->assertEquals($uri_external, $entity->field_test->uri);
    $this->assertEquals($uri_external, $entity->field_test[0]->uri);
    $this->assertEquals($boolean, $entity->field_test->boolean);
    $this->assertEquals($boolean, $entity->field_test[0]->boolean);
    $this->assertEquals($color, $entity->field_test->color);
    $this->assertEquals($color, $entity->field_test[0]->color);

    // Verify changing the field value.
    $new_string = $this->randomString(255);
    $new_string_long = $random->paragraphs(6);
    $new_integer = rand(11, 20);
    $new_float = rand(1001, 2000) / 100;
    $new_decimal = '18.2';
    $new_email = $this->randomMachineName();
    $new_telephone = '+41' . rand(1000000, 9999999);
    $new_uri_external = 'https://www.drupal.org';
    $new_boolean = 0;
    $new_color = '#FFFFFF';
    $entity->field_test->string = $new_string;
    $this->assertEquals($new_string, $entity->field_test->string);
    $entity->field_test->integer = $new_integer;
    $this->assertEquals($new_integer, $entity->field_test->integer);
    $entity->field_test->decimal = $new_decimal;
    $this->assertEquals($new_decimal, $entity->field_test->decimal);
    $entity->field_test->float = $new_float;
    $this->assertEquals($new_float, $entity->field_test->float);
    $entity->field_test->email = $new_email;
    $this->assertEquals($new_email, $entity->field_test->email);
    $entity->field_test->telephone = $new_telephone;
    $this->assertEquals($new_telephone, $entity->field_test->telephone);
    $entity->field_test->uri = $new_uri_external;
    $this->assertEquals($new_uri_external, $entity->field_test->uri);
    $entity->field_test->boolean = $new_boolean;
    $this->assertEquals($new_boolean, $entity->field_test->boolean);
    $entity->field_test->color = $new_color;
    $this->assertEquals($new_color, $entity->field_test->color);
    $entity->field_test->string_long = $new_string_long;
    $this->assertEquals(strlen($new_string_long), strlen($entity->field_test[0]->string_long));

    // Test sample item generation.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

}
