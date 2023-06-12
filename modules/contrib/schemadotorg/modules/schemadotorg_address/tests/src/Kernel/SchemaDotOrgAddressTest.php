<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_address\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org address module.
 *
 * @covers address_schemadotorg_jsonld_schema_property_alter(()
 * @group schemadotorg
 */
class SchemaDotOrgAddressTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'address',
    'schemadotorg_address',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_address']);
  }

  /**
   * Test Schema.org Blueprints Address installation.
   */
  public function testInstall(): void {
    \Drupal::moduleHandler()->loadInclude('schemadotorg_address', 'install');

    $config = \Drupal::config('schemadotorg.settings');

    // Check PostalAddress default field types is 'string_long'.
    $this->assertEquals(['string_long'], $config->get('schema_types.default_field_types.PostalAddress'));

    schemadotorg_address_install(FALSE);

    // Check PostalAddress default field types is now 'address' and 'string_long'.
    $this->assertEquals(['address', 'string_long'], $config->get('schema_types.default_field_types.PostalAddress'));

    schemadotorg_address_uninstall(FALSE);

    // Check PostalAddress default field types is 'string_long'.
    $this->assertEquals(['string_long'], $config->get('schema_types.default_field_types.PostalAddress'));
  }

  /**
   * Test Schema.org Blueprints Address field overrides.
   */
  public function testFieldOverrides(): void {
    \Drupal::moduleHandler()->loadInclude('schemadotorg_address', 'install');
    schemadotorg_address_install(FALSE);

    $this->createSchemaEntity('node', 'Place');

    // Check address field override settings.
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = FieldConfig::loadByName('node', 'place', 'schema_address');
    $expected_settings = [
      'available_countries' => [],
      'langcode_override' => NULL,
      'field_overrides' => [
        'givenName' => ['override' => 'optional'],
        'additionalName' => ['override' => 'optional'],
        'familyName' => ['override' => 'optional'],
        'organization' => ['override' => 'optional'],
      ],
      'fields' => [],
    ];
    $this->assertEquals($expected_settings, $field_config->getSettings());
  }

}
