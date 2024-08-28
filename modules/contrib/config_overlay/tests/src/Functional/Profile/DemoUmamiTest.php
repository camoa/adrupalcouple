<?php

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

/**
 * Tests installation of the Umami profile with Config Overlay.
 *
 * @group config_overlay
 */
class DemoUmamiTest extends ConfigOverlayTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    /* @see https://www.drupal.org/project/drupal/issues/2990234 */
    $this->translationFilesDirectory = $this->publicFilesDirectory . '/translations';
    mkdir($this->translationFilesDirectory, 0777, TRUE);

    // Prepare a translation file to avoid attempting to download a translation
    // file from the actual translation server during the test.
    file_put_contents("$this->root/$this->translationFilesDirectory/drupal-8.0.0.es.po", '');
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig(): array {
    $expected_config = parent::getExpectedConfig();

    unset($expected_config[StorageInterface::DEFAULT_COLLECTION]['filter.format.restricted_html']['roles']);

    return $expected_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['theme'] = [
      'claro' => 0,
      'umami' => 0,
    ];

    // The system site configuration is overridden by the test, so make it match
    // the values given in Umami's version of the file.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait::getOverriddenConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['page']['front'] = '/node';

    /* @see demo_umami_form_install_configure_submit() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['contact.form.feedback'] = [
      'recipients' => ['simpletest@example.com'],
    ];

    // Add text formats with a roles property.
    /* @see https://www.drupal.org/project/drupal/issues/3167198 */
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayDemoUmamiTest::getExpectedConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['filter.format.restricted_html'] = [];

    return $overridden_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getModules(): array {
    $modules = parent::getModules();
    $modules += [
      'demo_umami_content' => 0,
    ];
    return $modules;
  }

}
