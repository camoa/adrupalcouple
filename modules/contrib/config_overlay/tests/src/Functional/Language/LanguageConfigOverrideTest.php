<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Language;

use Drupal\Core\Config\StorageInterface;
use Drupal\language\Config\LanguageConfigFactoryOverrideInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayLanguageTestTrait;

/**
 * Tests installing with language overrides and Config Overlay.
 *
 * The test uses the "Testing multilingual" profile as that ships a German
 * language entity.
 *
 * @group config_overlay
 */
class LanguageConfigOverrideTest extends ConfigOverlayTestBase {

  use ConfigOverlayLanguageTestTrait;

  /**
   * The language configuration factory override used in this test.
   *
   * @var \Drupal\language\Config\LanguageConfigFactoryOverrideInterface
   */
  protected LanguageConfigFactoryOverrideInterface $languageConfigFactoryOverride;

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
  protected static $modules = ['language', 'language_config_override_test'];

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
    'de' => [],
    'es' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpServices(): void {
    parent::setUpServices();

    $this->languageConfigFactoryOverride = $this->container->get(LanguageConfigFactoryOverrideInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overriddenConfig = parent::getOverriddenConfig();
    $overriddenConfig[StorageInterface::DEFAULT_COLLECTION]['language.negotiation'] = $this->getLanguageNegotiationConfig();
    return $overriddenConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigExport(): void {
    parent::testConfigExport();

    // Test that the shipped language override was imported.
    // See language_config_override_test.settings.yml shipped by the
    // 'Language config override test' module.
    $languageOverride = $this->languageConfigFactoryOverride->getOverride('de', 'language_config_override_test.settings');
    $this->assertFalse($languageOverride->isNew());
    $this->assertSame(
      ['language_config_override_test.settings'],
      $this->configStorage->createCollection('language.de')->listAll(),
    );
    $this->assertEmpty($this->configStorage->createCollection('language.es')->listAll());
  }

}
