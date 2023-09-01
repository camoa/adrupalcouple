<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'custom_template' formatter.
 *
 * Render the customfield using a custom template with token replacement.
 *
 * @FieldFormatter(
 *   id = "custom_template",
 *   label = @Translation("Custom template"),
 *   weight = 4,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomTemplateFormatter extends CustomFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'template' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {

    $form = parent::settingsForm($form, $form_state);

    $tokens = [
      '#theme' => 'item_list',
    ];
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $label = $customItem->getLabel();
      $tokens['#items'][] = "[$name]: $label value";
      $tokens['#items'][] = "[$name:label]: $label label";
    }

    $form['template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template'),
      '#description' => $this->t('Output custom field items using a custom template. The following tokens are available for replacement: <br>') . \Drupal::service('renderer')->render($tokens),
      '#rows' => 5,
      '#default_value' => $this->getSetting('template'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary[] = $this->t('Template: @template', ['@template' => $this->getSetting('template')]);

    return $summary;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item): array {
    $replacements = [];
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $markup = $customItem->value($item);
      if ($markup === '' || $markup === NULL) {
        continue;
      }
      $replacements["[$name]"] = $markup;
      $replacements["[$name:label]"] = $customItem->getLabel();
    }
    $output = strtr($this->getSetting('template'), $replacements);

    return ['#markup' => $output];
  }

}
