<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_translate\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Translates a custom block (block_content) to Spanish using AI Translate.
 */
#[Action(
  id: 'adrupalcouple_translate_block_to_spanish',
  label: new TranslatableMarkup('Translate to Spanish (AI)'),
  type: 'block_content',
)]
final class TranslateBlockContentToSpanish extends TranslateToSpanishBase {

}
