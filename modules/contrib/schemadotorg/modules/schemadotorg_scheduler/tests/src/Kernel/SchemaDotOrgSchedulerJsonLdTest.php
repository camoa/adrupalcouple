<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_scheduler\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Scheduler module JSON-LD integration.
 *
 * @covers scheduler_schemadotorg_jsonld_schema_property_alter(()
 * @group schemadotorg
 */
class SchemaDotOrgSchedulerJsonLdTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'scheduler',
    'schemadotorg_scheduler',
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

    $this->installConfig(['schemadotorg_scheduler', 'schemadotorg_jsonld']);

    $this->builder = $this->container->get('schemadotorg_jsonld.builder');
  }

  /**
   * Test Schema.org scheduler JSON-LD.
   */
  public function testJsonLdAddress(): void {
    \Drupal::currentUser()->setAccount($this->createUser(['access content']));

    DateFormat::create([
      'id' => 'long',
      'label' => 'long',
      'pattern' => 'l, F j, Y - H:i',
    ])->save();

    $this->config('schemadotorg_scheduler.settings')
      ->set('scheduled_types.Article', ['publish', 'unpublish'])
      ->save();

    $this->createSchemaEntity('node', 'Article');

    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    $node = Node::create([
      'type' => 'article',
      'title' => 'Some article',
      'publish_on' => ['value' => strtotime('2020-01-01')],
      'unpublish_on' => ['value' => strtotime('2021-01-01')],
    ]);
    $node->save();

    $expected_value = [
      '@type' => 'Article',
      '@url' => $node->toUrl()->setAbsolute()->toString(),
      'inLanguage' => 'en',
      'headline' => 'Some article',
      'dateCreated' => $date_formatter->format($node->getCreatedTime(), 'custom', 'Y-m-d H:i:s P'),
      'dateModified' => $date_formatter->format($node->getChangedTime(), 'custom', 'Y-m-d H:i:s P'),
      'datePublished' => $date_formatter->format(strtotime('2020-01-01'), 'custom', 'Y-m-d H:i:s P'),
      'expires' => $date_formatter->format(strtotime('2021-01-01'), 'custom', 'Y-m-d H:i:s P'),
    ];
    $actual_value = $this->builder->buildEntity($node);
    $this->assertEquals($expected_value, $actual_value);
  }

}
