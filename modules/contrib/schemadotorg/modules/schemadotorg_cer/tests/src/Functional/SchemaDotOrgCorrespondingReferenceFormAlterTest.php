<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_cer\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org Corresponding Entity Reference form altering.
 *
 * @covers schemadotorg_cer_form_alter()
 * @group schemadotorg
 */
class SchemaDotOrgCorrespondingReferenceFormAlterTest extends SchemaDotOrgBrowserTestBase {

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
    'schemadotorg_cer',
  ];

  /**
   * Test Schema.org Corresponding Entity Reference form alter.
   */
  public function testFormAlter(): void {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check that no alterations are needed because there are no corresponding
    // reference entities.
    $this->drupalGet('/admin/config/content/cer/add');
    $assert_session->responseNotContains('<optgroup label="field">');
    $assert_session->responseNotContains('<optgroup label="schema">');

    // Create the Person and WebPage entities which creates the
    // 'Schema.org: Subject of ↔ About' corresponding reference entity.
    $this->createSchemaEntity('node', 'Person');
    $this->createSchemaEntity('node', 'WebPage');

    // Check that the corresponding reference entity form include 'schema_*'
    // field names.
    $this->drupalGet('/admin/config/content/cer/schema_subject_of');
    $assert_session->selectExists('first_field');
    $assert_session->responseNotContains('<optgroup label="field">');
    $assert_session->responseContains('<optgroup label="schema">');
    $assert_session->responseContains('<option value="schema_subject_of" selected="selected">schema_subject_of</option>');
    $assert_session->responseContains('<option value="schema_about" selected="selected">schema_about</option>');
    $assert_session->responseContains('<option value="node:*" selected="selected">node: *</option>');
  }

}
