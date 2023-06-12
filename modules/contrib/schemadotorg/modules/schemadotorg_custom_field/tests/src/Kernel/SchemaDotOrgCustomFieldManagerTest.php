<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_custom_field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org custom field manager.
 *
 * @covers \Drupal\schemadotorg_custom_field\SchemaDotOrgCustomFieldDefaultVocabularyManager
 * @group schemadotorg
 */
class SchemaDotOrgCustomFieldManagerTest extends SchemaDotOrgKernelEntityTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the custom field module has a schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'custom_field',
    'schemadotorg_custom_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_custom_field']);
  }

  /**
   * Test Schema.org custom field manager.
   */
  public function testManager(): void {
    // Create a Recipe and FAQPage.
    $this->createSchemaEntity('node', 'Recipe');
    $this->createSchemaEntity('node', 'FAQPage');

    /* ********************************************************************** */

    // Check recipe nutrition custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_nutrition');
    $expected_settings = [
      'columns' => [
        'serving_size' => [
          'name' => 'serving_size',
          'type' => 'string',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'calories' => [
          'name' => 'calories',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'carbohydrate_content' => [
          'name' => 'carbohydrate_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'cholesterol_content' => [
          'name' => 'cholesterol_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'fat_content' => [
          'name' => 'fat_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'fiber_content' => [
          'name' => 'fiber_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'protein_content' => [
          'name' => 'protein_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'saturated_fat_content' => [
          'name' => 'saturated_fat_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'sodium_content' => [
          'name' => 'sodium_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'sugar_content' => [
          'name' => 'sugar_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'trans_fat_content' => [
          'name' => 'trans_fat_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'unsaturated_fat_content' => [
          'name' => 'unsaturated_fat_content',
          'type' => 'decimal',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check FAQ page main entity custom field storage columns.
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = FieldStorageConfig::loadByName('node', 'schema_faq_main_entity');
    $expected_settings = [
      'columns' => [
        'name' => [
          'name' => 'name',
          'type' => 'string_long',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
        'accepted_answer' => [
          'name' => 'accepted_answer',
          'type' => 'string_long',
          'max_length' => '255',
          'unsigned' => 0,
          'precision' => '10',
          'scale' => '2',
        ],
      ],
    ];
    $this->assertEquals($expected_settings, $field_storage_config->getSettings());

    // Check recipe nutrition custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'recipe', 'schema_nutrition');
    $settings = $field_config->getSettings();
    $expected_settings_serving_size = [
      'type' => 'text',
      'widget_settings' => [
        'label' => 'Serving size',
        'settings' => [
          'description' => 'The serving size, in terms of the number of volume or mass.',
          'size' => 60,
          'placeholder' => '',
          'maxlength' => 255,
          'maxlength_js' => FALSE,
          'description_display' => 'after',
          'required' => FALSE,
          'prefix' => '',
          'suffix' => '',
        ],
      ],
      'check_empty' => '1',
      'weight' => 0,
    ];
    $this->assertEquals($expected_settings_serving_size, $settings['field_settings']['serving_size']);
    $expected_settings_calories = [
      'type' => 'decimal',
      'widget_settings' => [
        'label' => 'Calories',
        'settings' => [
          'description' => 'The number of calories.',
          'scale' => 2,
          'description_display' => 'after',
          'required' => FALSE,
          'suffix' => ' calories',
          'decimal_separator' => '.',
          'thousand_separator' => '',
        ],
      ],
      'formatter_settings' => ['prefix_suffix' => TRUE],
      'check_empty' => '1',
      'weight' => 1,
    ];
    $this->assertEquals($expected_settings_calories, $settings['field_settings']['calories']);

    // Check faq page main entity custom field column widget settings.
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'faq', 'schema_faq_main_entity');
    $settings = $field_config->getSettings();
    $expected_settings_serving_size = [
      'type' => 'textarea',
      'widget_settings' => [
        'label' => 'Question',
        'settings' => [
          'description' => 'The name of the item.',
          'rows' => 5,
          'placeholder' => '',
          'maxlength' => '',
          'maxlength_js' => FALSE,
          'formatted' => TRUE,
          'default_format' => 'basic_html',
          'format' => [
            'guidelines' => FALSE,
            'help' => FALSE,
          ],
          'description_display' => 'after',
          'required' => FALSE,
        ],
      ],
      'check_empty' => '1',
      'weight' => 0,
    ];
    $this->assertEquals($expected_settings_serving_size, $settings['field_settings']['name']);

    // Check custom field form display.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
    $entity_form_display = EntityFormDisplay::load('node.recipe.default');
    $components = $entity_form_display->getComponents();
    $this->assertEquals('custom_stacked', $components['schema_nutrition']['type']);
    $this->assertEquals('fieldset', $components['schema_nutrition']['settings']['wrapper']);
  }

}
