<?php

declare(strict_types=1);

namespace Drupal\Tests\markdown_easy\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Test installing Markdown Easy when Markdown input format already exists.
 *
 * @group markdown_easy
 */
final class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * Module handler, used to check which modules are installed.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  public $moduleInstaller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Simulate the `markdown` filter already being present.
    $markdown_format = FilterFormat::create([
      'format' => 'markdown',
      'name' => 'Markdown',
      'weight' => 1,
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => 2,
          'settings' => [
            'allowed_html' => "<strong> <em> <del>",
          ],
        ],
      ],
    ]);
    $markdown_format->save();

    $this->moduleHandler = $this->container->get('module_handler');
    $this->moduleInstaller = $this->container->get('module_installer');
  }

  /**
   * Check module not already installed. Install, and check installed.
   */
  public function testModuleInstall(): void {
    $this->assertFalse($this->moduleHandler->moduleExists('markdown_easy'));
    $this->assertTrue($this->moduleInstaller->install(['markdown_easy']));

    unset($this->moduleHandler);
    $this->rebuildContainer();
    $this->moduleHandler = $this->container->get('module_handler');

    $this->assertTrue($this->moduleHandler->moduleExists('markdown_easy'));
  }

}
