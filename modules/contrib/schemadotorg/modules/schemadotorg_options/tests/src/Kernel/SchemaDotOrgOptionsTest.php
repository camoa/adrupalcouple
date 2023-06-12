<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_options\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the Schema.org options.
 *
 * @group schemadotorg
 */
class SchemaDotOrgOptionsTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'schemadotorg_options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
  }

  /**
   * Test Schema.org options.
   */
  public function testEntityDisplayBuilder(): void {
    $this->appendSchemaTypeDefaultProperties('Person', ['gender']);
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Recipe');

    // Check that gender is assigned custom allowed values..
    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field_storage = FieldStorageConfig::load('node.schema_gender');
    $expected_allowed_values = [
      'Male' => 'Male',
      'Female' => 'Female',
      'Unspecified' => 'Unspecified',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));

    // Check that knowsLanguage is assigned an allowed values function.
    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field_storage = FieldStorageConfig::load('node.schema_knows_language');
    $this->assertEquals('schemadotorg_options_allowed_values_language', $field_storage->getSetting('allowed_values_function'));

    // Check that suitableForDiet    is assigned an allowed values function.
    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field_storage = FieldStorageConfig::load('node.schema_suitable_for_diet');
    $expected_allowed_values = [
      'DiabeticDiet' => 'Diabetic Diet',
      'GlutenFreeDiet' => 'Gluten Free Diet',
      'HalalDiet' => 'Halal Diet',
      'HinduDiet' => 'Hindu Diet',
      'KosherDiet' => 'Kosher Diet',
      'LowCalorieDiet' => 'Low Calorie Diet',
      'LowFatDiet' => 'Low Fat Diet',
      'LowLactoseDiet' => 'Low Lactose Diet',
      'LowSaltDiet' => 'Low Salt Diet',
      'VeganDiet' => 'Vegan Diet',
      'VegetarianDiet' => 'Vegetarian Diet',
    ];
    $this->assertEquals($expected_allowed_values, $field_storage->getSetting('allowed_values'));
  }

}
