<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Profile;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayTestBase;
use Drupal\Tests\config_overlay\Functional\ConfigOverlayLanguageTestTrait;

/**
 * Tests importing translations of shipped configuration with Config Overlay.
 *
 * @group config_overlay
 */
class TestingMultilingualTest extends ConfigOverlayTestBase {

  use ConfigOverlayLanguageTestTrait;

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
   * An array of translations used in this test.
   *
   * The keys of the array are the language codes of the translations and the
   * respective (language-specific) values are arrays where the keys are the
   * translation message identifiers and the values are the translation strings.
   *
   * @var string[][]
   */
  protected array $translationsByLanguage = [
    'de' => [
      // spellchecker:ignore Benutzerkonto
      'User account' => 'Benutzerkonto',
    ],
    'es' => [
      // spellchecker:ignore Cuenta usuario
      'User account' => 'Cuenta de usuario',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig(): array {
    $overridden_config = parent::getOverriddenConfig();

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation'] = $this->getLanguageNegotiationConfig();

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
