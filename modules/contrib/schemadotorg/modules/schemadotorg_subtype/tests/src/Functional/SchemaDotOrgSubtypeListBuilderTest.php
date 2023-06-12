<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_subtype\Functional;

use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;
use Drupal\Tests\schemadotorg_subtype\Traits\SchemaDotOrgTestSubtypeTrait;

/**
 * Tests the functionality of the Schema.org subtype list builder enhancements.
 *
 * @covers \Drupal\schemadotorg_subtype\EventSubscriber\SchemaDotOrgSubtypeEventSubscriber
 * @group schemadotorg
 */
class SchemaDotOrgSubtypeListBuilderTest extends SchemaDotOrgBrowserTestBase {
  use SchemaDotOrgTestSubtypeTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['schemadotorg_subtype'];

  /**
   * Test Schema.org list builder enhancements.
   */
  public function testSchemaDotOrgListBuilder(): void {
    $assert_session = $this->assertSession();

    // Create Thing content type with a Schema.org mapping.
    $this->drupalCreateContentType(['type' => 'thing']);
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = SchemaDotOrgMapping::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'thing',
      'schema_type' => 'Thing',
    ]);
    $mapping->save();

    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);

    /* ********************************************************************** */

    $this->drupalGet('/admin/config/search/schemadotorg');

    // Check subtype header.
    $assert_session->responseContains('<th class="priority-low" width="10%">Schema.org subtyping</th>');

    // Check subtype cell is set to No.
    $assert_session->responseContains('<td class="priority-low">No</td>');
    $assert_session->responseNotContains('<td class="priority-low">Yes</td>');

    // Add subtype property mapping.
    $mapping
      ->setSchemaPropertyMapping('schema_thing_subtype', 'subtype')
      ->save();

    $this->drupalGet('/admin/config/search/schemadotorg');

    // Check subtype cell is set to Yes.
    $assert_session->responseNotContains('<td class="priority-low">No</td>');
    $assert_session->responseContains('<td class="priority-low">Yes</td>');
  }

}
