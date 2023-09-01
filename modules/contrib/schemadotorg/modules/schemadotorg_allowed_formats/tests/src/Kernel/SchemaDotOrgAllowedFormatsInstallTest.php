<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_allowed_formats\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org allowed formats install hooks.
 *
 * @covers _schemadotorg_allowed_formats_apply(()
 * @group schemadotorg
 */
class SchemaDotOrgAllowedFormatsInstallTest extends SchemaDotOrgKernelEntityTestBase {
  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'filter',
    'allowed_formats',
    'schemadotorg_allowed_formats',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['schemadotorg_allowed_formats']);
    $this->installConfig('node');

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ])->save();

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');
  }

  /**
   * Test Schema.org allowed formats installation.
   */
  public function testAllowedFormatsInstallation(): void {
    $this->drupalCreateContentType(['type' => 'page']);
    \Drupal::moduleHandler()->loadInclude('schemadotorg_allowed_formats', 'install');
    schemadotorg_allowed_formats_install(FALSE);

    // Check settings default allowed formats.
    /** @var \Drupal\field\FieldConfigInterface $body_field */
    $body_field = FieldConfig::loadByName('node', 'page', 'body');
    $this->assertEquals(['full_html'], $body_field->getSetting('allowed_formats'));

    // Checking setting  hide help and hide guidelines.
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'page');
    $form_component = $form_display->getComponent('body');
    $expected_values = [
      'allowed_formats' => [
        'hide_help' => '1',
        'hide_guidelines' => '1',
      ],
    ];
    $this->assertEquals($expected_values, $form_component['third_party_settings']);
  }

}
