<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayLanguageTestTrait;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

/**
 * Tests installation of the Umami profile with Config Overlay.
 *
 * @group config_overlay
 */
class DemoUmamiTest extends ConfigOverlayTestBase {

  use ConfigOverlayLanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * An array of translations used in this test.
   *
   * The keys of the array are the language codes of the translations and the
   * respective (language-specific) values are arrays where the keys are the
   * translation message identifiers and the values are the translation strings.
   *
   * @var string[][]
   */
  protected array $translationsByLanguage = [
    'es' => [],
  ];

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
  protected function getCoreExtensionConfiguration(): array {
    $config = parent::getCoreExtensionConfiguration();
    $config['module'] = module_config_sort(
      $config['module']
      + ['demo_umami_content' => 0]
    );
    return $config;
  }

}
