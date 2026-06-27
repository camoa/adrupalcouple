<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_translate\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Translates a taxonomy term to Spanish using AI Translate.
 */
#[Action(
  id: 'adrupalcouple_translate_term_to_spanish',
  label: new TranslatableMarkup('Translate to Spanish (AI)'),
  type: 'taxonomy_term',
)]
final class TranslateTermToSpanish extends TranslateToSpanishBase {

}
