<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD builder.
 *
 * @covers \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilder;
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdBuilderTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'filter',
    'schemadotorg_jsonld',
  ];

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld']);
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org JSON-LD builder.
   */
  public function testBuilder(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $data_formatter */
    $data_formatter = \Drupal::service('date.formatter');
    $now = time();

    // Append subjectOf and alternateName to CreativeWork default properties.
    $default_properties = $this->config('schemadotorg.settings')
      ->get('schema_types.default_properties.CreativeWork');
    $this->config('schemadotorg.settings')
      ->set('schema_types.default_properties.CreativeWork', array_merge($default_properties, ['subjectOf', 'alternateName']))
      ->save();

    $this->createSchemaEntity('node', 'CreativeWork');

    FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ])->save();

    $node = Node::create([
      'type' => 'creative_work',
      'title' => 'Something',
      'schema_alternate_name' => [
        'value' => 'Something else',
      ],
      'schema_subject_of' => [
        ['value' => 'Some subject'],
      ],
      'body' => [
        'summary' => 'A summary',
        'value' => 'Some description',
        'format' => 'empty_format',
      ],
      'created' => $now,
      'changed' => $now,
    ]);
    $node->save();

    // Check building JSON-LD for an entity that is mapped to a Schema.org type.
    $expected_result = [
      '@type' => 'CreativeWork',
      '@url' => $node->toUrl()->setAbsolute()->toString(),
      'name' => 'Something',
      'alternateName' => [
        'Something else',
      ],
      'description' => 'A summary',
      'text' => 'Some description',
      'subjectOf' => [
        [
          '@type' => 'CreativeWork',
          'name' => 'Some subject',
        ],
      ],
      'inLanguage' => 'en',
      'dateCreated' => $data_formatter->format($now, 'custom', 'Y-m-d H:i:s P'),
      'dateModified' => $data_formatter->format($now, 'custom', 'Y-m-d H:i:s P'),
    ];
    $this->assertEquals($expected_result, $this->builder->buildEntity($node));
  }

}
