<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional;

/**
 * Supports testing Config Overlay with different languages.
 *
 * Classes using this should declare a $translationsByLanguage property.
 *
 * @see \Drupal\Tests\config_overlay\Functional\Language\LanguageTestingTest::$translationsByLanguage
 * @see \Drupal\Tests\config_overlay\Functional\Profile\TestingMultilingualTest::$translationsByLanguage
 * @see \Drupal\Tests\config_overlay\Functional\Profile\DemoUmamiTest::$translationsByLanguage
 * @see \Drupal\Tests\config_overlay\Functional\ExistingConfig\ExistingConfigLanguageConfigOverrideSyncTest::$translationsByLanguage
 */
trait ConfigOverlayLanguageTestTrait {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    // Prepare translation files to avoid attempting to download translation
    // files from the actual translation server during the test.
    $translationFilesDirectory = "$this->root/$this->publicFilesDirectory/translations";
    mkdir($translationFilesDirectory, 0777, TRUE);

    foreach ($this->translationsByLanguage as $languageCode => $translations) {
      $poLines = [
        'msgid ""',
        'msgstr ""',
        '',
      ];
      foreach ($translations as $messageId => $messageString) {
        $poLines[] = 'msgid "' . $messageId . '"';
        $poLines[] = 'msgstr "' . $messageString . '"';
        $poLines[] = '';
      }

      file_put_contents(
        filename: "$translationFilesDirectory/drupal-8.0.0.$languageCode.po",
        data: implode(PHP_EOL, $poLines),
      );
    }
  }

  /**
   * Returns the 'language.negotiation' configuration data for this test.
   */
  protected function getLanguageNegotiationConfig(): array {
    $languageCodes = array_keys($this->translationsByLanguage);
    /* @see \Drupal\language\Entity\ConfigurableLanguage::postSave() */
    /* @see language_negotiation_url_prefixes_update() */
    $prefixes = (count($languageCodes) === 1)
      ? array_fill_keys($languageCodes, '')
      : array_combine($languageCodes, $languageCodes);
    return [
      'url' => [
        'prefixes' => $prefixes,
        'domains' => array_fill_keys($languageCodes, ''),
      ],
    ];
  }

}
