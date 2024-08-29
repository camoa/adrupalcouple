<?php

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;

// cspell:ignore Benutzerkonto

/**
 * Tests the Testing multilingual profile with Config Overlay.
 *
 * @group config_overlay
 */
class TestingMultilingualTest extends ConfigOverlayTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_multilingual';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected array $collections = [
    StorageInterface::DEFAULT_COLLECTION,
    'language.de',
    'language.es',
  ];

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    /* @see https://www.drupal.org/project/drupal/issues/2990234 */
    $this->translationFilesDirectory = $this->publicFilesDirectory . '/translations';
    mkdir($this->translationFilesDirectory, 0777, TRUE);

    // Prepare translation files to avoid attempting to download translation
    // files from the actual translation server during the test. The files
    // contain an actual translation to test that translations for configuration
    // are imported correctly.
    $po_de = <<<PO
msgid ""
msgstr ""

msgid "User account"
msgstr "Benutzerkonto"
PO;
    file_put_contents("$this->root/$this->translationFilesDirectory/drupal-8.0.0.de.po", $po_de);
    // cspell:ignore Cuenta usuario
    $po_fr = <<<PO
msgid ""
msgstr ""

msgid "User account"
msgstr "Cuenta de usuario"
PO;
    file_put_contents("$this->root/$this->translationFilesDirectory/drupal-8.0.0.es.po", $po_fr);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    /* @see \Drupal\language\Entity\ConfigurableLanguage::postSave() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation'] = [
      'url' => [
        'prefixes' => [
          'de' => 'de',
          'es' => 'es',
        ],
        'domains' => [
          'de' => '',
          'es' => '',
        ],
      ],
    ];

    /* @see https://www.drupal.org/node/3348540 */
    if (version_compare(\Drupal::VERSION, '9.5.8', '>=')) {
      /* @see locale_test_translate_modules_installed() */
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]['locale_test_translate.settings'] = [
        'key_set_during_install' => TRUE,
      ];
    }

    // Add overrides for translated configuration.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingLanguageTest::prepareEnvironment() */
    $overridden_config['language.de']['core.entity_view_mode.user.full'] = [
      'label' => 'Benutzerkonto',
    ];
    $overridden_config['language.es']['core.entity_view_mode.user.full'] = [
      'label' => 'Cuenta de usuario',
    ];

    return $overridden_config;
  }

}
