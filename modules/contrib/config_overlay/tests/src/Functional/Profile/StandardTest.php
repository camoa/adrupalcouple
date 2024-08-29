<?php

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

/**
 * Tests installation of the Standard profile with Config Overlay.
 *
 * @group config_overlay
 */
class StandardTest extends ConfigOverlayTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig(): array {
    $expected_config = parent::getExpectedConfig();

    foreach (['basic_html', 'full_html', 'restricted_html'] as $format_id) {
      unset($expected_config[StorageInterface::DEFAULT_COLLECTION]["filter.format.$format_id"]['roles']);
    }

    return $expected_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['theme'] = [
      'olivero' => 0,
      'claro' => 0,
    ];

    // The system site configuration is overridden by the test, so make it match
    // the values given in Standard's version of the file.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestTrait::getOverriddenConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['page']['front'] = '/node';

    /* @see standard_form_install_configure_submit() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['contact.form.feedback'] = [
      'recipients' => ['simpletest@example.com'],
    ];

    // The Claro theme settings are incorrectly not part of the shipped
    // configuration.
    // @todo Open an issue for this.
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['claro.settings'] = [
      'third_party_settings' => [
        'shortcut' => [
          'module_link' => TRUE,
        ],
      ],
    ];

    // Add text formats.
    /* @see https://www.drupal.org/project/drupal/issues/3167198 */
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayStandardTest::getExpectedConfig() */
    foreach (['basic_html', 'full_html', 'restricted_html'] as $format_id) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["filter.format.$format_id"] = [];
    }

    return $overridden_config;
  }

}
