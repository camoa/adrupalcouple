<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_jsonld_embed\Kernel;

use Drupal\Core\Session\UserSession;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org JSON-LD embed.
 *
 * @group schemadotorg
 */
class SchemaDotOrgJsonLdEmbedTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'filter',
    'schemadotorg_jsonld',
    'schemadotorg_jsonld_embed',
  ];

  /**
   * Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $manager;

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['schemadotorg_jsonld']);
    $this->manager = $this->container->get('schemadotorg_jsonld.manager');
    $this->builder = $this->container->get('schemadotorg_jsonld.builder');

    $this->dateFormatter = $this->container->get('date.formatter');

    // Set current user to admin.
    $account = new UserSession([
      'uid' => 1,
    ]);
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Test Schema.org JSON-LD embed.
   */
  public function testEmbed(): void {
    // Allow Schema.org Thing to have default properties.
    $this->config('schemadotorg.settings')
      ->set('schema_types.default_properties.Thing', ['name', 'description'])
      ->save();

    $this->createMediaImage();
    $this->createSchemaEntity('media', 'ImageObject');
    $this->createSchemaEntity('node', 'Thing');

    // Filter format.
    FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ])->save();

    // Image file.
    $file = $this->createFileImage();

    // Media.
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Some image',
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'default alt',
        'title' => 'default title',
      ],
    ]);
    $media->save();

    // Node.
    $node = Node::create([
      'type' => 'thing',
      'title' => 'Some thing',
      'langcode' => 'es',
      'body' => [
        'value' => '<p>Some description</p><drupal-media data-entity-type="media" data-entity-uuid="' . $media->uuid() . '"></drupal-media>',
        'format' => 'empty_format',
      ],
    ]);
    $node->save();

    /* ********************************************************************** */

    // Check building JSON-LD while include embedded media (and content).
    $image_style_storage = \Drupal::entityTypeManager()->getStorage('image_style');

    /** @var \Drupal\image\ImageStyleInterface $thumbnail_image_style */
    $large_image_style = $image_style_storage->load('large');
    /** @var \Drupal\image\ImageStyleInterface $thumbnail_image_style */
    $thumbnail_image_style = $image_style_storage->load('thumbnail');

    $expected_result = [
      0 => [
        '@context' => 'https://schema.org',
        '@type' => 'ImageObject',
        '@url' => $media->toUrl()->setAbsolute()->toString(),
        'inLanguage' => 'en',
        'name' => 'Some image',
        'dateCreated' => $this->dateFormatter->format($media->getCreatedTime(), 'custom', 'Y-m-d H:i:s P'),
        'dateModified' => $this->dateFormatter->format($media->getChangedTime(), 'custom', 'Y-m-d H:i:s P'),
        'image' => $large_image_style->buildUrl($media->field_media_image->entity->getFileUri()),
        'thumbnail' => $thumbnail_image_style->buildUrl($media->thumbnail->entity->getFileUri()),
      ],
      [
        '@context' => 'https://schema.org',
        '@type' => 'Thing',
        '@url' => $node->toUrl()->setAbsolute()->toString(),
        'name' => 'Some thing',
        'description' => '<p>Some description</p><drupal-media data-entity-type="media" data-entity-uuid="' . $media->uuid() . '"></drupal-media>',
      ],
    ];
    $route_match = $this->manager->getEntityRouteMatch($node);
    $this->assertEquals($expected_result, $this->builder->build($route_match));
  }

}
