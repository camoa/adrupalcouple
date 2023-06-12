<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_descriptions\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org descriptions.
 *
 * @group schemadotorg
 */
class SchemaDotOrgDescriptionsTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'field_ui',
    'block',
    'help',
    'schemadotorg_ui',
    'schemadotorg_subtype',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->placeBlock('help_block');
  }

  /**
   * Test Schema.org descriptions.
   */
  public function testDescriptions(): void {
    $assert_session = $this->assertSession();

    // Login as node type administrator.
    $account = $this->drupalCreateUser([
      'administer schemadotorg',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($account);

    // Check add content type, subtype, and field descriptions.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $assert_session->fieldValueEquals('mapping[entity][description]', 'The most generic type of item.');
    $assert_session->fieldValueEquals('mapping[properties][subtype][field][_add_][description]', 'A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->fieldValueEquals('mapping[properties][description][field][_add_][description]', 'A description of the item.');

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->install(['schemadotorg_descriptions']);

    // Check add content type, subtype, and field descriptions are empty and
    // the element's #description is updated.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Thing']]);
    $assert_session->fieldValueEquals('mapping[entity][description]', '');
    $assert_session->fieldValueEquals('mapping[properties][subtype][field][_add_][description]', '');
    $assert_session->fieldValueEquals('mapping[properties][description][field][_add_][description]', '');
    $assert_session->responseContains("<strong>If left blank, the description will be automatically set to the corresponding Schema.org type's comment.</strong>");
    $assert_session->responseContains("<strong>If left blank, the description will be automatically set.</strong>");

    // Create the 'Thing' content type with type and alternateName fields.
    $edit = [
      'mapping[properties][subtype][field][name]' => TRUE,
      'mapping[properties][alternateName][field][name]' => '_add_',
    ];
    $this->submitForm($edit, 'Save');

    // Create another random content type to enable the node add page.
    $this->drupalCreateContentType();

    // Login as root user since we are not testing node access.
    $this->drupalLogin($this->rootUser);

    // Check that the description is automatically added to the node types page.
    $this->drupalGet('/admin/structure/types');
    $assert_session->responseContains('The most generic type of item.');

    // Check that the description is automatically added to the node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseContains('The most generic type of item.');

    // Check that the descriptions are automatically added to the node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseContains('The most generic type of item.');
    $assert_session->responseContains('A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->responseContains('An alias for the item.');

    // Add custom descriptions for Thing and alternateName.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/general');
    $edit = [
      'schemadotorg_descriptions[custom_descriptions]' => 'Thing|This is a custom description for a Thing.'
      . PHP_EOL . 'alternateName|This is a custom description for an alternateName',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check that the custom description is automatically added to the
    // node types page.
    $this->drupalGet('/admin/structure/types');
    $assert_session->responseNotContains('The most generic type of item.');
    $assert_session->responseContains('This is a custom description for a Thing.');

    // Check that the custom description is automatically added to the
    // node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseNotContains('The most generic type of item.');
    $assert_session->responseContains('This is a custom description for a Thing.');

    // Check that the custom descriptions are automatically added to the
    // node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseNotContains('An alias for the item.');
    $assert_session->responseContains('This is a custom description for a Thing.');
    $assert_session->responseContains('This is a custom description for an alternateName');

    // Add custom descriptions for Thing and alternateName.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/general');
    $edit = [
      'schemadotorg_descriptions[custom_descriptions]' => 'Thing|This is a custom description for a Thing.'
      . PHP_EOL . 'alternateName|This is a custom description for an alternateName'
      . PHP_EOL . 'Thing--alternateName|This is a custom description for an Thing--alternateName',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check that the Thing--alternateName custom description is uses.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseContains('This is a custom description for a Thing.');
    $assert_session->responseNotContains('This is a custom description for an alternateName');
    $assert_session->responseContains('This is a custom description for an Thing--alternateName');

    // Remove custom descriptions for Thing and alternateName.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/general');
    $edit = [
      'schemadotorg_descriptions[custom_descriptions]' => 'Thing' . PHP_EOL . 'alternateName',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check that NO custom description is added to the node types page.
    $this->drupalGet('/admin/structure/types');
    $assert_session->responseNotContains('The most generic type of item.');
    $assert_session->responseNotContains('This is a custom description for a Thing.');

    // Check that NO custom description is added to the node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseNotContains('The most generic type of item.');
    $assert_session->responseNotContains('This is a custom description for a Thing.');

    // Check that NOT custom descriptions are added to the node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseNotContains('An alias for the item.');
    $assert_session->responseNotContains('This is a custom description for a Thing.');
    $assert_session->responseNotContains('This is a custom description for an alternateName');

    // Create 'Offer' with 'price' which has a long description.
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'Offer']]);
    $this->submitForm([], 'Save');

    // Check that the price and priceCurrency descriptions are trimmed.
    $this->drupalGet('/node/add/offer');
    $assert_session->responseContains('The offer price of a product, or of a price component when attached to PriceSpecification and its subtypes.');
    $assert_session->responseNotContains('Usage guidelines:');

    // Disable trim descriptions.
    $this->drupalGet('/admin/config/search/schemadotorg/settings/general');
    $edit = ['schemadotorg_descriptions[trim_descriptions]' => FALSE];
    $this->submitForm($edit, 'Save configuration');

    // Check that the price and priceCurrency descriptions are NOT trimmed.
    $this->drupalGet('/node/add/offer');
    $assert_session->responseContains('Usage guidelines:');

    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $module_installer->uninstall(['schemadotorg_descriptions']);

    // Check that the descriptions are not added to the node add page.
    $this->drupalGet('/node/add');
    $assert_session->responseNotContains('The most generic type of item.');

    // Check that descriptions are not added to the node edit form.
    $this->drupalGet('/node/add/thing');
    $assert_session->responseNotContains('A more specific subtype for the item. This is used to allow more specificity without having to create dedicated Schema.org entity types.');
    $assert_session->responseNotContains('An alias for the item.');
  }

}
