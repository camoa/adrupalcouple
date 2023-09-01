<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_paragraphs\Kernel;

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org paragraphs type icon file.
 *
 * @covers schemadotorg_paragraphs_paragraphs_type_presave()
 * @group schemadotorg
 */
class SchemaDotOrgParagraphsTypeIconFileTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'schemadotorg_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('file', ['file_usage']);

    $this->installEntitySchema('file');
    $this->installConfig(['schemadotorg_paragraphs']);
  }

  /**
   * Test Schema.org paragraphs type icon file.
   */
  public function testParagraphsTypeIconFile(): void {
    // Check that icon file is assigned to questions paragraph type.
    $this->createSchemaEntity('paragraph', 'Question');
    /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type */
    $paragraphs_type = ParagraphsType::load('question');
    $this->assertNotNull($paragraphs_type->getIconFile());
    $this->assertEquals(
      'public://paragraphs_type_icon/question.svg',
      $paragraphs_type->getIconFile()->getFileUri()
    );

    // Check that icon file is assigned to med_* paragraph type.
    $this->createSchemaEntity('paragraph', 'MedicalAudience');
    /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraphs_type */
    $paragraphs_type = ParagraphsType::load('medical_audience');
    $this->assertNotNull($paragraphs_type->getIconFile());
    $this->assertEquals(
      'public://paragraphs_type_icon/medical.svg',
      $paragraphs_type->getIconFile()->getFileUri()
    );
  }

}
