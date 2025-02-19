<?php

declare(strict_types=1);

namespace Drupal\fivestar\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Implementations of hooks defined by the Fivestar module.
 */
final class FivestarFivestarHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new FivestarFivestarHooks service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_fivestar_widgets().
   */
  #[Hook('fivestar_widgets')]
  public function fivestarWidgets(): array {
    // Widgets defined by the core Fivestar module.
    $widgets = [
      'basic' => [
        'library' => 'fivestar/basic',
        'label' => $this->t('Basic'),
      ],
      'craft' => [
        'library' => 'fivestar/craft',
        'label' => $this->t('Craft'),
      ],
      'drupal' => [
        'library' => 'fivestar/drupal',
        'label' => $this->t('Drupal'),
      ],
      'flames' => [
        'library' => 'fivestar/flames',
        'label' => $this->t('Flames'),
      ],
      'hearts' => [
        'library' => 'fivestar/hearts',
        'label' => $this->t('Hearts'),
      ],
      'lullabot' => [
        'library' => 'fivestar/lullabot',
        'label' => $this->t('Lullabot'),
      ],
      'minimal' => [
        'library' => 'fivestar/minimal',
        'label' => $this->t('Minimal'),
      ],
      'outline' => [
        'library' => 'fivestar/outline',
        'label' => $this->t('Outline'),
      ],
      'oxygen' => [
        'library' => 'fivestar/oxygen',
        'label' => $this->t('Oxygen'),
      ],
      'small' => [
        'library' => 'fivestar/small',
        'label' => $this->t('Small'),
      ],
    ];

    return $widgets;
  }

}
