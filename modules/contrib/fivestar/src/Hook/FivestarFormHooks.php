<?php

declare(strict_types=1);

namespace Drupal\fivestar\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Hook implementations used to alter and enhance forms.
 */
final class FivestarFormHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new FivestarFormHooks service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    TranslationInterface $string_translation,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_form_comment_form_alter().
   *
   * This hook removes the parent node, together with the fivestar field, from
   * the comment preview page. If this is left in, when the user presses the
   * "Save" button after the preview page has been displayed, the fivestar
   * widget gets the input rather than the comment; the user's input is lost.
   */
  #[Hook('form_comment_form_alter')]
  public function commentFormAlter(array &$form, FormStateInterface &$form_state, string $form_id): void {
    $fivestar_field_keys = [];
    if (isset($form['comment_output_below'])) {
      foreach ($form['comment_output_below'] as $key => $value) {
        if (is_array($value) && !empty($value['#field_type']) && $value['#field_type'] == 'fivestar') {
          $fivestar_field_keys[] = $key;
        }
      }
    }
    if ($fivestar_field_keys) {
      foreach ($fivestar_field_keys as $key) {
        unset($form['comment_output_below'][$key]);
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_field_ui_field_edit_form_alter')]
  public function fieldUiFieldEditFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var Drupal\field\FieldStorageConfigInterface $field */
    $field = $form['#field'];
    if ($field->getType() == 'fivestar') {
      // Multiple values is not supported with Fivestar.
      $form['field']['cardinality']['#access'] = FALSE;
      $form['field']['cardinality']['#value'] = 1;
      // Setting "default value" here is confusing and for all practical
      // purposes meaningless with existing widgets provided by fivestar (and
      // anything else available in contrib).
      $form['instance']['default_value_widget']['#access'] = FALSE;
    }
  }

}
