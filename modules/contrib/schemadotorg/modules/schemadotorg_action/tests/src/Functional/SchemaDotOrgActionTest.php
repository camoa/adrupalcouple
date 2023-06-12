<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_action\Functional;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org action.
 *
 * @covers schemadotorg_action_schemadotorg_jsonld_schema_type_entity_load()
 * @covers schemadotorg_action_schemadotorg_jsonld_schema_type_field_alter()
 * @covers schemadotorg_action_link_alter()
 * @covers schemadotorg_action_paragraph_view_alter()
 * @group schemadotorg
 */
class SchemaDotOrgActionTest extends SchemaDotOrgBrowserTestBase {
  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'schemadotorg_jsonld',
    'schemadotorg_layout_paragraphs',
    'schemadotorg_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create media image dependency before installing
    // the Schema.org Action module.
    $this->createMediaType('image', ['id' => 'image']);

    // Install the Schema.org Action and Layout Paragraphs modules.
    $this->container->get('module_installer')->install([
      'schemadotorg_action',
    ]);
    $this->container = \Drupal::getContainer();
  }

  /**
   * Test Schema.org call to action.
   */
  public function testCallToAction(): void {
    $assert_session = $this->assertSession();

    // Check that the CTA and CTA's paragraph type was installed.
    $this->assertNotNull(ParagraphsType::load('cta'));
    $this->assertNotNull(ParagraphsType::load('ctas'));

    // Create a page content type which use layout paragraphs.
    // @todo Determine why the below code is not working as expected.
    // $this->createSchemaEntity('node', 'WebPage');
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/types/schemadotorg', ['query' => ['type' => 'WebPage']]);
    $this->submitForm([], 'Save');

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test page',
      'schema_main_entity' => [
        Paragraph::create([
          'type' => 'cta',
          'schema_name' => 'Test call to action',
          'schema_significant_link' => [
            'title' => 'Test paragraph link',
            'uri' => 'https://yahoo.com',
            'options' => [
              'attributes' => [
                'schema_potential_action' => 'ViewAction',
                'class' => ['button'],
              ],
            ],
          ],
        ]),
        Paragraph::create([
          'type' => 'ctas',
          'schema_name' => 'Test call to actions',
          'schema_ctas_item_list_element' => [
            Paragraph::create([
              'type' => 'cta',
              'schema_name' => 'Nested call to action',
              'schema_significant_link' => [
                'title' => 'Test paragraph link',
                'uri' => 'https://microsoft.com',
                'options' => [
                  'attributes' => [
                    'schema_potential_action' => 'ViewAction',
                    'class' => ['button'],
                  ],
                ],
              ],
            ]),
          ],
        ]),
      ],
      'schema_significant_link' => [
        'title' => 'Test web page link',
        'uri' => 'https://google.com',
      ],
    ]);
    $node->save();

    /** @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface $builder */
    $builder = $this->container->get('schemadotorg_jsonld.builder');
    $data = $builder->buildEntity($node);

    // Check that the mainEntity does NOT include potentialAction.
    $expected_value = [
      '@type' => 'WebPage',
      'inLanguage' => 'en',
      'name' => 'Test call to action',
      'significantLink' => ['https://yahoo.com'],
      'position' => 1,
    ];
    $this->assertEquals($expected_value, $data['mainEntity'][0]);
    $expected_value = [
      '@type' => 'ItemList',
      'itemListElement' => [
        [
          '@type' => 'WebPage',
          'inLanguage' => 'en',
          'name' => 'Nested call to action',
          'significantLink' => ['https://microsoft.com'],
        ],
      ],
    ];
    $this->assertEquals($expected_value, $data['mainEntity'][1]);
    // Check that the WebPage pull the potentialAction from the mainEntity.
    $expected_value = [
      [
        '@action' => 'ViewAction',
        'target' => 'https://yahoo.com',
      ],
      [
        '@action' => 'ViewAction',
        'target' => 'https://microsoft.com',
      ],
    ];
    $this->assertEquals($expected_value, $data['potentialAction']);

    // Get the rendered WebPage.
    $this->drupalGet('/node/' . $node->id());

    // Check that the render node does not include
    // the schema_potential_action attributes.
    $assert_session->responseNotContains('schema_potential_action');

    // Check that the render node does include button class.
    $assert_session->responseContains('<a href="https://yahoo.com" class="button">Test paragraph link</a>');
  }

}
