<?php

namespace Drupal\dropdown_language\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides language switcher block plugin definitions for all languages.
 */
class DropdownLanguage extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new DropdownLanguage instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Creates the derivative instance using the service container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param string $base_plugin_id
   *   The base plugin ID.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get configurable language types and their info.
    $configurable_types = $this->languageManager->getLanguageTypes();
    $info = $this->languageManager->getDefinedLanguageTypesInfo();

    foreach ($configurable_types as $type) {
      $this->derivatives[$type] = $base_plugin_definition;
      $this->derivatives[$type]['admin_label'] = $this->t('Dropdown Language (@type)', ['@type' => $info[$type]['name']]);
    }

    // If there's only one configurable type, update the block title.
    if (count($configurable_types) === 1) {
      $this->derivatives[reset($configurable_types)]['admin_label'] = $this->t('Dropdown Language');
    }

    return $this->derivatives;
  }

}
