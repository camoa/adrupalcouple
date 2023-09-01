<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_epp\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org Entity Prepopulate.
 *
 * @group schemadotorg
 */
class SchemaDotOrgEntityPrepopulateTest extends SchemaDotOrgKernelEntityTestBase {

  // phpcs:disable
  /**
   * Disabled config schema checking until the cer.module has fixed its schema.
   */
  protected $strictConfigSchema = FALSE;
  // phpcs:enable

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'cer',
    'schemadotorg_cer',
    'epp',
    'schemadotorg_epp',
  ];

  /**
   * The Schema.org mapping manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface
   */
  protected $mappingManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(self::$modules);

    \Drupal::moduleHandler()->loadInclude('schemadotorg_cer', 'install');
    schemadotorg_cer_install(FALSE);
  }

  /**
   * Test Schema.org entity prepopulate.
   */
  public function testEntityPrepopulate(): void {
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'Organization');
    $this->createSchemaEntity('node', 'LocalBusiness');

    /* ********************************************************************** */

    $fields = [
      'node.person.schema_member_of' => 'target_id: [current-page:query:member_of]',
      'node.organization.schema_parent_organization' => 'target_id: [current-page:query:parent_organization]',
      'node.organization.schema_subject_of' => 'target_id: [current-page:query:subject_of]',
      'node.organization.schema_sub_organization' => 'target_id: [current-page:query:sub_organization]',
      'node.person.schema_works_for' => 'target_id: [current-page:query:works_for]',
    ];
    foreach ($fields as $id => $value) {
      $this->assertEquals($value, FieldConfig::load($id)->getThirdPartySetting('epp', 'value'));
    }
  }

}
