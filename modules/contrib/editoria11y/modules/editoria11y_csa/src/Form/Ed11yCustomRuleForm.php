<?php

declare(strict_types=1);

namespace Drupal\editoria11y_csa\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editoria11y_csa\Entity\Ed11yCustomRule;

/**
 * Editoria11y Custom Test form.
 */
final class Ed11yCustomRuleForm extends EntityForm {

  /**
   * The entity being edited.
   *
   * @var \Drupal\editoria11y_csa\Entity\Ed11yCustomRule
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin label for the configuration page'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['test_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test grouping ID'),
      '#description' => $this->t('Letters, numbers and underscores only.<br>Rules that share an ID will be grouped on dashboard reports.'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('test_key') ?: $this->entity->id(),
      '#required' => TRUE,
      '#pattern' => '[a-zA-Z0-9_]+',
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [Ed11yCustomRule::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->entity->status(),
    ];

    $form['tip'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Alert content'),
    ];

    $form['tip']['test_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tip title'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->get('test_name'),
      '#required' => TRUE,
    ];

    $form['tip']['tip_content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tip details'),
      '#description' => $this->t('HTML displayed in the tooltip. The provided template is optional.<br>
        Allowed tags: @tags.', [
          '@tags' => '<p> <a href> <em> <strong class> <code> <ul> <ol> <li> <div class>',
        ]),
      '#default_value' => $this->entity->get('tip_content') ?: '<p></p> 
<p><strong class="badge">' . $this->t('To fix') . '</strong></p>
<div class="why"><div>',
      '#required' => TRUE,
    ];

    $form['tip']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Alert type'),
      '#options' => [
        'error' => $this->t('Error'),
        'warning' => $this->t('Manual check'),
      // Remove from translation
      // 'error' => $this->t('Error (cannot be dismissed)'),
        // 'warning' => $this->t('Warning (can be dismissed)'),.
      ],
      '#default_value' => $this->entity->get('type') ?: 'error',
    ];

    $form['tip']['dismiss_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Dismiss key'),
      '#description' => $this->t("How dismissals are tracked. 'Text' uses its text content, 'Attributes' uses its attributes (href, id, class, src)."),
      '#options' => [
        'text' => $this->t('Text content'),
        'attributes' => $this->t('Attributes on tag (e.g. href and src)'),
      ],
      '#default_value' => $this->entity->get('dismiss_key') ?: 'text',
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'warning'],
        ],
      ],
    ];

    $form['elements'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Elements to check'),
      '#markup' => '<p>' . $this->t('These sets contain elements that match your check area and skip over configuration.') . '</p>',
    ];

    // Parse the stored comma-separated value into a sorted array for preset
    // detection. array_filter handles the empty-string case from explode.
    $stored_set = array_values(array_filter(explode(',', $this->entity->get('element_set') ?? '')));
    $stored_sorted = $stored_set;
    sort($stored_sorted);

    $text_preset = ['Blockquotes', 'Headings', 'Lists', 'Paragraphs'];
    if (empty($stored_sorted) || $stored_sorted === $text_preset) {
      $base_default = 'Text';
    }
    elseif ($stored_sorted === ['Links']) {
      $base_default = 'Links';
    }
    elseif ($stored_sorted === ['Images']) {
      $base_default = 'Images';
    }
    elseif ($stored_sorted === ['Everything']) {
      $base_default = 'Everything';
    }
    else {
      $base_default = 'Specify';
    }

    $form['elements']['base_elements'] = [
      '#type' => 'select',
      '#title' => $this->t('Common choices'),
      '#options' => [
        'Text' => $this->t('All text elements: Headings, Paragraphs, Blockquotes and Lists'),
        'Links' => $this->t('Links'),
        'Paragraphs' => $this->t('Paragraphs'),
        'Headings' => $this->t('Headings'),
        'Images' => $this->t('Images'),
        'Everything' => $this->t('All elements'),
        'Specify' => $this->t('...other...'),
      ],
      '#default_value' => $base_default,
    ];

    $form['elements']['element_set'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Specific elements and combinations'),
      '#options' => [
        'Paragraphs' => $this->t('Paragraphs'),
        'Headings' => $this->t('Headings'),
        'Lists' => $this->t('Lists'),
        'Images' => $this->t('Images'),
        'Links' => $this->t('Links'),
        'Blockquotes' => $this->t('Blockquotes'),
        'Tables' => $this->t('Tables'),
        'Buttons' => $this->t('Buttons'),
        'Inputs' => $this->t('Inputs'),
        'Labels' => $this->t('Labels'),
        'iframes' => $this->t('Iframes'),
        'Videos' => $this->t('Videos'),
        'Audio' => $this->t('Audio'),
      ],
      '#default_value' => $stored_set,
      '#description' => $this->t('Note: combining Links with Paragraphs and other text containers can lead to duplicate alerts.'),
      '#states' => [
        'visible' => [
          ':input[name="base_elements"]' => ['value' => 'Specify'],
        ],
      ],
    ];

    $form['rule'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('When tip should appear'),
      '#markup' => '<p>' . $this->t('This test builder allows for simple text and attribute tests.<br>Check the module readme for <a href="@url">tips on writing more complex tests</a>.', ['@url' => 'https://git.drupalcode.org/project/editoria11y']) . '</p>',
    ];

    $form['rule']['filter_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select by attributes'),
      '#description' => $this->t('Match using CSS selectors.<br>Use :not() to skip elements, e.g. <code>:is(.check1, .check2):not(.ignore, .ignore *)</code>.<br>Use attribute selectors for URLs, e.g. <code>[href*=".dev/"]</code>.'),
      '#maxlength' => 512,
      '#default_value' => $this->entity->get('filter_selector'),
    ];

    $form['rule']['include_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Select by text'),
      '#description' => $this->t('Flag elements containing any of these text strings. One per line.'),
      '#default_value' => implode("\n", $this->entity->get('include_text') ?: []),
    ];

    $form['rule']['case_sensitive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#default_value' => $this->entity->get('case_sensitive'),
    ];

    $form['rule']['exclude_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude elements that match this text'),
      '#description' => $this->t("Don't flag elements if they contain any of these words or phrases. E.g., a link to a development server is ok if it contains \"dev.\" One per line."),
      '#default_value' => implode("\n", $this->entity->get('exclude_text') ?: []),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    // This method may be called multiple times (validation + submission).
    // Only convert when the value is still a raw textarea string.
    $include = $form_state->getValue('include_text');
    if (is_string($include)) {
      $form_state->setValue('include_text', $this->textareaToArray($include));
    }
    $exclude = $form_state->getValue('exclude_text');
    if (is_string($exclude)) {
      $form_state->setValue('exclude_text', $this->textareaToArray($exclude));
    }
    $tip = $form_state->getValue('tip_content');
    if (is_string($tip)) {
      $allowed_tags = ['p', 'a', 'em', 'strong', 'code', 'ul', 'ol', 'li'];
      $form_state->setValue('tip_content', Xss::filter($tip, $allowed_tags));
    }

    // Map the UI preset + optional checkboxes to the stored element_set string.
    // Only runs on the first call (base_elements is unset afterward).
    $base = $form_state->getValue('base_elements');
    if ($base !== NULL) {
      $preset_map = [
        'Text' => 'Paragraphs,Headings,Lists,Blockquotes',
        'Links' => 'Links',
        'Images' => 'Images',
        'Everything' => 'Everything',
      ];
      if (isset($preset_map[$base])) {
        $form_state->setValue('element_set', $preset_map[$base]);
      }
      else {
        // 'Specify': collect the checked checkbox values.
        $checked = array_keys(array_filter($form_state->getValue('element_set') ?? []));
        $form_state->setValue('element_set', implode(',', $checked));
      }
      $form_state->unsetValue('base_elements');
    }

    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate test_key contains only alphanumeric characters and underscores.
    $test_key = $form_state->getValue('test_key');
    if (!empty($test_key) && !preg_match('/^[a-z0-9_]+$/i', $test_key)) {
      $form_state->setErrorByName('test_key', $this->t('Test key must contain only letters, numbers, and underscores.'));
    }

    // Validate tip_content has visible text after filtering.
    $tip = $form_state->getValue('tip_content');
    if (!empty($tip)) {
      $filtered = Xss::filter($tip);
      if (empty(trim(strip_tags($filtered)))) {
        $form_state->setErrorByName('tip_content', $this->t('Tip content must contain visible text.'));
      }
    }

    if (empty($form_state->getValue('filter_selector')) && empty($form_state->getValue('include_text'))) {
      $form_state->setErrorByName('rule', $this->t('Select at least one rule.'));
    }

    // Require at least one checkbox when 'Specify' is selected.
    if ($form_state->getValue('base_elements') === 'Specify') {
      $checked = array_filter($form_state->getValue('element_set') ?? []);
      if (empty($checked)) {
        $form_state->setErrorByName('element_set', $this->t('Select at least one element type.'));
      }
    }

    // Validate filter_selector is a selector, not a CSS rule block.
    $selector = $form_state->getValue('filter_selector');
    if (!empty($selector) && preg_match('/[{}]/', $selector)) {
      $form_state->setErrorByName('filter_selector', $this->t('The filter selector should be a CSS selector, not a CSS rule block.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match ($result) {
        \SAVED_NEW => $this->t('Created new custom test %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated custom test %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

  /**
   * Converts a newline-delimited string to an array of trimmed strings.
   */
  private function textareaToArray(string $value): array {
    if (empty($value)) {
      return [];
    }
    return array_values(array_filter(array_map('trim', explode("\n", $value))));
  }

}
