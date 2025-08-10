<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Language;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayLanguageTestTrait;

/**
 * Tests installing with a different language with Config Overlay.
 *
 * @group config_overlay
 */
class LanguageTestingTest extends ConfigOverlayTestBase {

  use ConfigOverlayLanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected string $langcode = 'af';

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
    'af' => [
      // spellchecker:ignore Gebruikersrekening
      'User account' => 'Gebruikersrekening',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = $this->langcode;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Document this
   */
  protected function doTestRecreateInitial(ConfigEntityInterface $initial_entity): ConfigEntityInterface {
    $recreated_entity = $this->recreateEntity($initial_entity);

    $this->exportConfig();

    return $recreated_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['config_overlay.deleted'] = [
      'names' => ['language.entity.en'],
    ];

    // Add overrides for translated configuration.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingLanguageTest::prepareEnvironment() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.entity_view_mode.user.full'] = [
      'label' => 'Gebruikersrekening',
    ];

    /* @see drupal_install_system() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['default_langcode'] = $this->langcode;

    /* @see install_download_additional_translations_operations() */
    /* @see \Drupal\Core\Config\Entity\ConfigEntityType::getPropertiesToExport() */
    $language = ConfigurableLanguage::createFromLangcode($this->langcode);
    $language_config_name = 'language.entity.' . $this->langcode;
    $overridden_config[StorageInterface::DEFAULT_COLLECTION][$language_config_name] = [
      'uuid' => $this->configStorage->read($language_config_name)['uuid'],
      'langcode' => $this->langcode,
      'status' => TRUE,
      'dependencies' => [],
      'id' => $this->langcode,
      'label' => $language->label(),
      'direction' => $language->getDirection(),
      'weight' => 0,
      'locked' => FALSE,
    ];

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation'] = $this->getLanguageNegotiationConfig();

    // All configuration will specify the site default language as their its
    // language while the shipped configuration specifies English.
    foreach ($this->configStorage->listAll() as $config_name) {
      if ($config_name === $language_config_name) {
        continue;
      }

      // @todo Investigate why this changed and whether that is a bug. It seems to be related to
      //   https://www.drupal.org/node/3348540
      if (version_compare(\Drupal::VERSION, '9.5.8', '>=') && ($config_name === 'config_overlay.deleted')) {
        continue;
      }

      // See https://www.drupal.org/node/3427629
      // @todo Make this more generic.
      $untranslatable_config = [
        'core.extension',
        'core.menu.static_menu_link_overrides',
        'field.settings',
        'file.settings',
        'language.mappings',
        'language.negotiation',
        'language.types',
        'locale.settings',
        'system.advisories',
        'system.cron',
        'system.date',
        'system.diff',
        'system.feature_flags',
        'system.file',
        'system.image',
        'system.image.gd',
        'system.logging',
        'system.mail',
        'system.performance',
        'system.rss',
        'system.theme.global',
        'system.theme',
        'user.flood',
      ];
      if (version_compare(\Drupal::VERSION, '10.3.0', '>=') && in_array($config_name, $untranslatable_config, TRUE)) {
        continue;
      }

      $overridden_config[StorageInterface::DEFAULT_COLLECTION] += [$config_name => []];
      $overridden_config[StorageInterface::DEFAULT_COLLECTION][$config_name] += ['langcode' => $this->langcode];
    }

    return $overridden_config;
  }

}
