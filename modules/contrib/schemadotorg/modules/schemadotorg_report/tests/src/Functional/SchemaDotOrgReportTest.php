<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_report\Functional;

use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests for Schema.org report.
 *
 * @group schemadotorg
 */
class SchemaDotOrgReportTest extends SchemaDotOrgBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'schemadotorg_report'];

  /**
   * A user with permission to access site reports.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $reportUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->reportUser = $this->drupalCreateUser(['access site reports']);

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test report routes, controllers, and form.
   *
   * This a baseline test that confirms the Schema.org report renders
   * as expected with the expected page title.
   */
  public function testReport(): void {
    global $base_path;

    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    // Check that anonymous users can't access the Schema.org report.
    $this->drupalGet('/admin/reports/schemadotorg');
    $assert_session->statusCodeEquals(403);

    // Login account with 'access site reports' permission.
    $this->drupalLogin($this->reportUser);

    // Check about (index) page.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportItemController::about
    $this->drupalGet('/admin/reports/schemadotorg');
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('<h1>Schema.org: About</h1>');

    // Check find Schema.org type form.
    $this->submitForm(['id' => 'Thing'], 'Find');
    $assert_session->addressEquals('/admin/reports/schemadotorg/Thing');
    $assert_session->responseContains('<h1>Schema.org: Thing (Type)</h1>');

    // Check that the 'About' page/tab points to the same URL.
    // @see schemadotorg_report_menu_local_tasks_alter()
    $this->drupalGet('/admin/reports/schemadotorg');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/reports/schemadotorg" data-drupal-link-system-path="admin/reports/schemadotorg">About<span class="visually-hidden">(active tab)</span></a>');
    $this->drupalGet('/admin/reports/schemadotorg/Thing');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/reports/schemadotorg" data-drupal-link-system-path="admin/reports/schemadotorg">About<span class="visually-hidden">(active tab)</span></a>');
    $this->drupalGet('/admin/reports/schemadotorg/name');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/reports/schemadotorg" data-drupal-link-system-path="admin/reports/schemadotorg">About<span class="visually-hidden">(active tab)</span></a>');

    /* ********************************************************************** */

    // Check Schema.org type item.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportItemController::item
    $this->drupalGet('/admin/reports/schemadotorg/Thing');
    $assert_session->responseContains('<h1>Schema.org: Thing (Type)</h1>');

    // Check Schema.org property item.
    $this->drupalGet('/admin/reports/schemadotorg/name');
    $assert_session->responseContains('<h1>Schema.org: name (Property)</h1>');

    /* ********************************************************************** */

    // Check Schema.org types table.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportTableController::index
    $this->drupalGet('/admin/reports/schemadotorg/docs/types');
    $assert_session->responseContains('<h1>Schema.org: Types</h1>');

    // Check find Schema.org type form.
    $this->submitForm(['id' => 'Thing'], 'Find');
    $assert_session->addressEquals('/admin/reports/schemadotorg/Thing');
    $assert_session->responseContains('<h1>Schema.org: Thing (Type)</h1>');

    /* ********************************************************************** */

    // Check Things hierarchical tree.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportHierarchyController::index
    $this->drupalGet('/admin/reports/schemadotorg/docs/things');
    $assert_session->responseContains('<h1>Schema.org: Things</h1>');

    // Check Intangibles hierarchical tree.
    $this->drupalGet('/admin/reports/schemadotorg/docs/intangibles');
    $assert_session->responseContains('<h1>Schema.org: Intangibles</h1>');

    // Check Enumerations hierarchical tree.
    $this->drupalGet('/admin/reports/schemadotorg/docs/enumerations');
    $assert_session->responseContains('<h1>Schema.org: Enumerations</h1>');

    // Check Structured values hierarchical tree.
    $this->drupalGet('/admin/reports/schemadotorg/docs/structured-values');
    $assert_session->responseContains('<h1>Schema.org: Structured values</h1>');

    // Check Data types hierarchical tree.
    $this->drupalGet('/admin/reports/schemadotorg/docs/data-types');
    $assert_session->responseContains('<h1>Schema.org: Data types</h1>');

    /* ********************************************************************** */

    // Check Schema.org properties table.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportTableController::index
    $this->drupalGet('/admin/reports/schemadotorg/docs/properties');
    $assert_session->responseContains('<h1>Schema.org: Properties</h1>');

    // Check find Schema.org property form.
    $this->submitForm(['id' => 'name'], 'Find');
    $assert_session->addressEquals('/admin/reports/schemadotorg/name');
    $assert_session->responseContains('<h1>Schema.org: name (Property)</h1>');

    /* ********************************************************************** */

    // Check Schema.org names overview.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportNamesController::overview
    $this->drupalGet('/admin/reports/schemadotorg/docs/names');
    $assert_session->responseContains('<h1>Schema.org: Names overview</h1>');

    // Check Schema.org all names tables.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReportNamesController::table
    $this->drupalGet('/admin/reports/schemadotorg/docs/names/all');
    $assert_session->responseContains('<h1>Schema.org: All names</h1>');
    $assert_session->responseContains('2286 items');

    // Check Schema.org type names tables.
    $this->drupalGet('/admin/reports/schemadotorg/docs/names/types');
    $assert_session->responseContains('<h1>Schema.org: Type names</h1>');
    $assert_session->responseContains('829 types');

    // Check Schema.org property names tables.
    $this->drupalGet('/admin/reports/schemadotorg/docs/names/properties');
    $assert_session->responseContains('<h1>Schema.org: Property names</h1>');
    $assert_session->responseContains('1457 properties');

    // Check Schema.org property names tables.
    $this->drupalGet('/admin/reports/schemadotorg/docs/names/abbreviations');
    $assert_session->responseContains('<h1>Schema.org: Abbreviated names</h1>');

    /* ********************************************************************** */

    // Check Schema.org reference.
    // @see \Drupal\schemadotorg_report\Controller\SchemaDotOrgReferencesController::index
    $this->drupalGet('/admin/reports/schemadotorg/docs/references');
    $assert_session->responseContains('<h1>Schema.org: References</h1>');
  }

}
