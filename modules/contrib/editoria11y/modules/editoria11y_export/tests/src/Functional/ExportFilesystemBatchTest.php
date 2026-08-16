<?php

namespace Drupal\Tests\editoria11y_export\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\editoria11y\Traits\UserTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the CSV export from install through batch export, both filesystems.
 *
 * This guards two things that depend on Views Data Export and can silently
 * drift:
 * - The install-time filesystem selection performed by
 *   hook_modules_installed(): "private" when a private filesystem is available,
 *   "public" otherwise.
 * - The generated CSV: its header (the field mapping), that a seeded page is
 *   actually exported, and that cells leading with a spreadsheet formula
 *   trigger are apostrophe-escaped by hook_views_data_export_row_alter().
 *
 * Both are asserted for the private variant (a private filesystem is available,
 * the functional-test default) and the public variant (private removed).
 *
 * @noinspection PhpUndefinedMethodInspection
 */
#[Group('editoria11y')]
#[Group('editoria11y_export')]
class ExportFilesystemBatchTest extends BrowserTestBase {

  use UserTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The export view uses aggregation, which trips the strict schema checker.
   *
   * @var bool
   */
  // phpcs:ignore DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   *
   * The editoria11y_export submodule is deliberately absent: each test installs
   * it after establishing the filesystem availability it needs to exercise, so
   * that hook_modules_installed() runs under the intended conditions.
   */
  protected static $modules = [
    'editoria11y',
    'node',
    'taxonomy',
    'user',
    'views',
    'views_data_export',
    'csv_serialization',
    // Views Data Export writes the export file via the file.repository service
    // but does not declare a dependency on the file module.
    'file',
  ];

  /**
   * The CSV header the pages export is expected to emit (the field mapping).
   */
  private const EXPECTED_HEADER = [
    'Content alerts',
    'Dev alerts',
    'Page',
    'Path when checked',
    'Current URL',
    'Author',
    'Type',
    'Language',
    'Authored on',
  ];

  /**
   * The data_export display ids that carry the export_filesystem option.
   */
  private const EXPORT_DISPLAYS = [
    'data_export_pages',
    'data_export_dismissals',
    'data_export_results',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->drupalCreateNode(['type' => 'page', 'title' => 'Test Page']);
    $this->drupalCreateNode(['type' => 'page', 'title' => '=2+2']);

    // A dashboard user can both submit results (view checker) and reach the
    // export displays (manage results).
    $this->drupalLogin($this->setUpDashboardUser());

    // Seed results through the real ingestion endpoint: one ordinary page and
    // one whose title is a spreadsheet formula, to cover CSV injection
    // escaping in the export.
    $this->seedResult('Test Page', 1);
    $this->seedResult('=2+2', 2);
  }

  /**
   * Private filesystem available: install selects private and exports there.
   */
  public function testPrivateVariantInstallToExport(): void {
    $this->assertTrue(
      \Drupal::service('stream_wrapper_manager')->isValidScheme('private'),
      'Functional tests provide a private filesystem by default.'
    );

    $this->installExportAndAssertScheme('private');
    $this->assertPagesExport('private');
  }

  /**
   * Private filesystem removed: install falls back to public and exports there.
   */
  public function testPublicVariantInstallToExport(): void {
    // Rebuild the container without a private filesystem before installing, so
    // hook_modules_installed() sees only the public scheme.
    $settings['settings']['file_private_path'] = (object) [
      'value' => '',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    $this->rebuildContainer();
    $this->assertFalse(
      \Drupal::service('stream_wrapper_manager')->isValidScheme('private'),
      'Private filesystem should be unavailable for the public variant.'
    );

    $this->installExportAndAssertScheme('public');
    $this->assertPagesExport('public');
  }

  /**
   * Installs the export submodule and asserts the resolved export filesystem.
   *
   * @param string $expected_scheme
   *   The scheme every data_export display should be set to.
   */
  private function installExportAndAssertScheme(string $expected_scheme): void {
    $this->assertTrue(
      \Drupal::service('module_installer')->install(['editoria11y_export']),
      'editoria11y_export installed successfully.'
    );
    $this->resetAll();

    $displays = \Drupal::config('views.view.ed11y_export')->get('display');
    foreach (self::EXPORT_DISPLAYS as $display_id) {
      $options = $displays[$display_id]['display_options'] ?? [];
      $this->assertSame(
        $expected_scheme,
        $options['export_filesystem'] ?? NULL,
        "$display_id should use the $expected_scheme filesystem."
      );
      $this->assertArrayNotHasKey(
        'store_in_public_file_directory',
        $options,
        "$display_id should no longer carry the obsolete option."
      );
    }
  }

  /**
   * Runs the pages batch export and asserts the CSV and its storage location.
   *
   * @param string $expected_scheme
   *   The filesystem the generated file is expected to live in.
   */
  private function assertPagesExport(string $expected_scheme): void {
    // With automatic_download the batch ends in a file transfer instead of a
    // completion page; disable it so this non-JS test can drive the batch
    // deterministically. It affects neither the CSV contents nor the mapping.
    \Drupal::configFactory()->getEditable('views.view.ed11y_export')
      ->set('display.data_export_pages.display_options.automatic_download', FALSE)
      ->save();
    $this->resetAll();

    // Visiting the export path sets up and runs the Views Data Export batch.
    $this->drupalGet('admin/reports/editoria11y/export/editoria11y_pages.csv');
    $this->assertSession()->statusCodeEquals(200);

    // Views Data Export saves the generated file as a temporary managed file.
    $fids = \Drupal::entityTypeManager()->getStorage('file')->getQuery()
      ->accessCheck(FALSE)
      ->condition('uri', '%/views_data_export/ed11y_export_data_export_pages/%', 'LIKE')
      ->sort('fid', 'DESC')
      ->range(0, 1)
      ->execute();
    $this->assertNotEmpty($fids, 'The batch export produced a managed file.');

    $file = File::load(reset($fids));
    $this->assertStringStartsWith(
      $expected_scheme . '://',
      $file->getFileUri(),
      "Export file should be stored in the $expected_scheme filesystem."
    );

    $csv = file_get_contents($file->getFileUri());
    $this->assertNotEmpty($csv, 'Export file should not be empty.');

    $lines = preg_split('/\r\n|\n|\r/', trim($csv));
    // The export uses RFC-style CSV (enclosure doubling, no escape character),
    // so pass an empty escape argument — also required on PHP 8.4+.
    $header = str_getcsv($lines[0], ',', '"', '');
    $this->assertSame(
      self::EXPECTED_HEADER,
      $header,
      'CSV header should match the expected field mapping.'
    );
    $this->assertGreaterThanOrEqual(
      3,
      count($lines),
      'CSV should contain the header plus both seeded data rows.'
    );
    $this->assertStringContainsString(
      'Test Page',
      $csv,
      'The seeded page should appear in the export.'
    );

    // The formula-titled page must arrive apostrophe-escaped, never as a cell
    // a spreadsheet application would execute.
    $page_index = array_search('Page', $header, TRUE);
    $pages = [];
    foreach (array_slice($lines, 1) as $line) {
      $cells = str_getcsv($line, ',', '"', '\\');
      $pages[] = $cells[$page_index] ?? NULL;
    }
    $this->assertContains(
      "'=2+2",
      $pages,
      'A formula-leading title should be apostrophe-escaped in the batch export.'
    );
    $this->assertNotContains(
      '=2+2',
      $pages,
      'No cell should lead with a live formula trigger.'
    );
  }

  /**
   * Submits one page of results through the report API endpoint.
   *
   * @param string $title
   *   The reported page title.
   * @param int $nid
   *   The node id backing the reported page.
   */
  private function seedResult(string $title, int $nid): void {
    $payload = [
      'page_title' => $title,
      'page_path' => '/node/' . $nid,
      'entity_id' => $nid,
      'content_total' => 3,
      'dev_total' => 2,
      'language' => 'en',
      'entity_type' => 'node',
      'route_name' => 'entity.node.canonical',
      'results' => [
        'LINK_STOPWORD' => [
          'content_count' => 3,
          'dev_count' => 0,
          'result_name' => 'Link text may not be meaningful',
        ],
      ],
      'oks' => [],
      'hides' => [],
    ];

    $this->drupalGet('/session/token');
    $token = $this->getSession()->getPage()->getContent();
    $client = $this->getSession()->getDriver()->getClient();
    $client->request(
      'POST',
      $this->baseUrl . '/editoria11y/api/results/report?_format=json',
      [],
      [],
      [
        'HTTP_X_CSRF_TOKEN' => $token,
        'CONTENT_TYPE' => 'application/json',
      ],
      Json::encode($payload)
    );
    $this->assertEquals(
      200,
      $this->getSession()->getStatusCode(),
      'Seed report should be accepted.'
    );
  }

}
