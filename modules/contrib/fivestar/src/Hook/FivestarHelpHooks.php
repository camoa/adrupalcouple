<?php

declare(strict_types=1);

namespace Drupal\fivestar\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Hook implementations used to provide help.
 */
final class FivestarHelpHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new FivestarHelpHooks service.
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
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.fivestar':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The Fivestar voting module is a very simple rating module that provides the possibility to rate items with stars or similar items. This gives you the possibilities to rate various items or even to create online forms for evaluations and assessments with different questions to be answered. For more information, see the <a href=":online">online documentation for the Fivestar module</a>.', [':online' => 'https://www.drupal.org/documentation/modules/fivestar']) . '</p>';
        $output .= '<h3>' . $this->t('Uses') . '</h3>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('General') . '</dt>';
        $output .= '<dd>' . $this->t("The Fivestar module can be used to easily rate various types of content on your website. These ratings can be used on the content itself or even from the comments of that piece of content.") . '</dd>';
        $output .= '<dt>' . $this->t('Basic Concepts and Features') . '</dt>';
        $output .= '<dd>' . $this->t('Fivestar is an excellent voting widget first made available for use on Drupal 5 websites. The D5 module included the ability to create a voting widget for nodes. With Drupal 6 came the ability to add comments. And with Drupal 7, the web developer was given the ability to create the voting widget with any entity.') . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

}
