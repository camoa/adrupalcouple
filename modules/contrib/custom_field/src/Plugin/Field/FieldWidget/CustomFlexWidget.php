<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'custom_flex' widget.
 *
 * @FieldWidget(
 *   id = "custom_flex",
 *   label = @Translation("Flexbox"),
 *   weight = 0,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomFlexWidget extends CustomWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'breakpoint' => '',
      'columns' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;
    $elements['#attached']['library'][] = 'custom_field/custom-field-flex';
    $elements['#attached']['library'][] = 'custom_field/custom-field-flex-admin';

    $elements['columns'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Column settings'),
      '#description' => $this->t('Select the number of columns for each form element. A value of <em>auto</em> will size the column based on the natural width of content within it.'),
      '#description_display' => 'before',
    ];

    $elements['columns']['prefix'] = [
      '#markup' => '<div class="custom-field-row custom-field-flex--widget-settings">',
    ];

    $columns = $this->getSettings()['columns'];
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $plugin_id = $customItem->getPluginId();
      // The uuid widget type is a hidden field.
      if ($plugin_id == 'uuid') {
        continue;
      }
      $elements['columns'][$name] = [
        '#type' => 'select',
        '#title' => $customItem->getLabel(),
        '#options' => $this->columnOptions(),
        '#wrapper_attributes' => [
          'class' => ['custom-field-col'],
        ],
        '#attributes' => [
          'class' => ['custom-field-col__field'],
        ],
      ];
      if (isset($columns[$name])) {
        $elements['columns'][$name]['#default_value'] = $columns[$name];
        $elements['columns'][$name]['#wrapper_attributes']['class'][] = 'custom-field-col-' . $columns[$name];
      }

      $is_disabled = FALSE;
      switch ($plugin_id) {
        case 'color_boxes':
        case 'map_key_value':
          $is_disabled = TRUE;
          break;

        case 'textarea':
          if ($customItem->getWidgetSetting('settings')['formatted']) {
            $is_disabled = TRUE;
          }
          break;
      }

      if ($is_disabled) {
        $elements['columns'][$name]['#default_value'] = 12;
        $elements['columns'][$name]['#attributes'] = ['disabled' => TRUE];
        $elements['columns'][$name]['#description'] = $this->t('This widget type as configured requires full width.');
      }
    }

    $elements['columns']['suffix'] = [
      '#markup' => '</div>',
    ];

    $elements['breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Stack items on:'),
      '#description' => $this->t('The device width in which the columns are set to full width and stack on top of one another.'),
      '#options' => $this->breakpointOptions(),
      '#default_value' => $this->getSetting('breakpoint'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    $columns = 'Automatic';
    if (!empty($this->getSettings()['columns'])) {
      $columns = implode(' | ', $this->getSettings()['columns']);
    }
    $summary[] = $this->t('Column settings: @columns', ['@columns' => $columns]);
    $summary[] = $this->t('Stack on: @breakpoint', ['@breakpoint' => $this->breakpointOptions($this->getSetting('breakpoint'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#attached']['library'][] = 'custom_field/custom-field-flex';
    $classes = ['custom-field-row'];
    if ($this->getSetting('breakpoint')) {
      $classes[] = 'custom-field-flex--stack-' . $this->getSetting('breakpoint');
    }
    // Using markup since we can't nest values because the field api expects
    // subfields to be at the top-level.
    $element['wrapper_prefix']['#markup'] = '<div class="' . implode(' ', $classes) . '">';

    $columns = $this->getSettings()['columns'];
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $element[$name] = $customItem->widget($items, $delta, $element, $form, $form_state);
      $element[$name]['#wrapper_attributes']['class'][] = 'custom-field-col';
      if (isset($columns[$name])) {
        $element[$name]['#wrapper_attributes']['class'][] = 'custom-field-col-' . $columns[$name];
      }
    }

    $element['wrapper_suffix']['#markup'] = '</div>';

    return $element;
  }

  /**
   * Get the field storage definition.
   */
  public function getFieldStorageDefinition(): FieldStorageDefinitionInterface {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * The options for columns.
   */
  public function columnOptions($option = NULL) {
    $options = [
      'auto' => $this->t('Auto'),
      1 => $this->t('1 column'),
      2 => $this->t('2 columns'),
      3 => $this->t('3 columns'),
      4 => $this->t('4 columns'),
      5 => $this->t('5 columns'),
      6 => $this->t('6 columns'),
      7 => $this->t('7 columns'),
      8 => $this->t('8 columns'),
      9 => $this->t('9 columns'),
      10 => $this->t('10 columns'),
      11 => $this->t('11 columns'),
      12 => $this->t('12 columns'),
    ];
    if (!is_null($option)) {
      return $options[$option] ?? '';
    }

    return $options;
  }

  /**
   * The options for breakpoints.
   */
  public function breakpointOptions($option = NULL) {
    $options = [
      '' => $this->t("Don't stack"),
      'medium' => $this->t('Medium (less than 769px)'),
      'small' => $this->t('Small (less than 601px)'),
    ];
    if (!is_null($option)) {
      return $options[$option] ?? '';
    }

    return $options;
  }

}
