<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_starterkti\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Starterkit module.
 *
 * @group schemadotorg
 */
class SchemaDotOrgStarterKitTest extends SchemaDotOrgBrowserTestBase {

  // phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  /**
   * Disabled config schema checking temporarily until smart date fixes missing schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_starterkit_test', 'schemadotorg_starterkit_update_test'];

  /**
   * Test Schema.org actions before a module is installed.
   *
   * @covers schemadotorg_starterkit_module_preinstall()
   */
  public function testPreInstall(): void {
    // Check that rewritten schema_types.default_properties in
    // schemadotorg.settings.yml are unique and sorted.
    // @see https://www.drupal.org/project/config_rewrite/issues/3152228
    $this->assertEquals(
      ['articleBody', 'author', 'headline', 'image', 'keywords'],
      \Drupal::config('schemadotorg.settings')->get('schema_types.default_properties.Article'),
    );

    // Check that node types were created.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $node_type_storage */
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');
    $this->assertNotNull($node_type_storage->load('person'));
    $this->assertNotNull($node_type_storage->load('event'));
    $this->assertNotNull($node_type_storage->load('thing'));

    // Check that Schema.org mappings were created.
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $mapping_storage = \Drupal::entityTypeManager()->getStorage('schemadotorg_mapping');
    $this->assertNotNull($mapping_storage->load('node.person'));
    $this->assertNotNull($mapping_storage->load('node.event'));
    $this->assertNotNull($mapping_storage->load('node.thing'));

    // Check that the events view configuration was imported.
    // @see schemadotorg_preinstall_test/config/optional/views.view.events.yml
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingStorageInterface $mapping_storage */
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $this->assertNotNull($view_storage->load('events'));

    // Check Thing custom defaults were applied.
    // @see schemadotorg_preinstall_test.schemadotorg.yml
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $node_type_storage->load('thing');
    $this->assertEquals('Something', $node_type->label());

    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $mapping_storage->load('node.thing');
    $expected_properties = [
      'schema_description' => 'description',
      'schema_image' => 'image',
      'schema_name' => 'name',
    ];
    $this->assertEquals($expected_properties, $mapping->getSchemaProperties());

    // Check node.person properties includes honorific suffix/prefix
    // and family name.
    // This check confirms that starterkits can only add properties to
    // existing Schema.org types.
    // @see schemadotorg_starterkit_test.schemadotorg_starterkit.yml
    // @see schemadotorg_starterkit_update_test.schemadotorg_starterkit.yml
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $mapping_storage->load('node.person');
    $expected_properties = [
      'schema_additional_name' => 'additionalName',
      'body' => 'description',
      'schema_email' => 'email',
      'schema_family_name' => 'familyName',
      'schema_given_name' => 'givenName',
      'schema_honorific_prefix' => 'honorificPrefix',
      'schema_honorific_suffix' => 'honorificSuffix',
      'schema_image' => 'image',
      'schema_knows_language' => 'knowsLanguage',
      'schema_member_of' => 'memberOf',
      'title' => 'name',
      'schema_same_as' => 'sameAs',
      'schema_telephone' => 'telephone',
      'schema_works_for' => 'worksFor',
    ];
    $this->assertEquals($expected_properties, $mapping->getSchemaProperties());
  }


}
