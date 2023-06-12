<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'custom_default' widget.
 *
 * @FieldWidget(
 *   id = "custom_default",
 *   label = @Translation("Customfield"),
 *   weight = 0,
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class CustomWidget extends CustomWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'customize' => FALSE,
      'breakpoint' => '',
      'proportions' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;
    $elements['#attached']['library'][] = 'custom_field/customfield-inline';
    $elements['#attached']['library'][] = 'custom_field/customfield-inline-admin';

    $id = Html::getUniqueId('customfield-inline-customize');
    $elements['customize'] = [
      '#type' => 'checkbox',
      '#title' => t('Customize Customfield item proportions'),
      '#description' => t('By default the items will automatically resize to the most optimal size based on their content. Check this box to give specific proportions to the field items.'),
      '#default_value' => $this->getSetting('customize'),
      '#attributes' => [
        'data-id' => $id,
      ],
    ];

    $elements['proportions'] = [
      '#type' => 'fieldset',
      '#title' => t('Proportions'),
      '#description' => t('The size of the item relative to the other items. Example: If you had three items and gave respective proportions of 1/1/2, the resulting fields would be 25%/25%/50%. The above drop downs will resize as you change the values to reflect how the items will be output.'),
      '#states' => array(
         'visible' => array(
           ':input[data-id="' . $id . '"]' => ['checked' => TRUE],
         ),
       ),
    ];

    $elements['proportions']['prefix'] = [
      '#markup' => '<div class="customfield-inline customfield-inline--widget-settings">',
    ];

    $proportions = $this->getSettings()['proportions'];
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customitem */
    foreach ($this->getCustomFieldItems() as $name => $customitem) {
      $elements['proportions'][$name] = [
        '#type' => 'select',
        '#title' => $customitem->getLabel(),
        '#options' => $this->proportionOptions(),
        '#wrapper_attributes' => [
          'class' => ['customfield-inline__item']
        ],
        '#attributes' => [
          'class' => ['customfield-inline__field']
        ],
      ];
      if (isset($proportions[$name])) {
        $elements['proportions'][$name]['#default_value'] = $proportions[$name];
        $elements['proportions'][$name]['#wrapper_attributes']['class'][] = 'customfield-inline__item--' . $proportions[$name];
      }
    }

    $elements['proportions']['suffix'] = [
      '#markup' => '</div>',
    ];

    $elements['breakpoint'] = [
      '#type' => 'select',
      '#title' => t('Stack items on:'),
      '#description' => t('The device width below which we stack the inline customfield items.'),
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

    $proportions = 'Automatic';
    if (!empty($this->getSetting('customize')) && !empty($this->getSettings()['proportions'])) {
      $proportions = implode(' | ', $this->getSettings()['proportions']);
    }
    $summary[] = t('Inline Customfield items.');
    $summary[] = t('Item Proportions: @proportions', ['@proportions' => $proportions]);
    $summary[] = t('Stack on: @breakpoint', ['@breakpoint' => $this->breakpointOptions($this->getSetting('breakpoint'))]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#attached']['library'][] = 'custom_field/customfield-inline';
    $classes = ['customfield-inline'];
    if ($this->getSetting('breakpoint')) {
      $classes[] = 'customfield-inline--stack-' . $this->getSetting('breakpoint');
    }
    // Using markup since we can't nest values because the field api expects
    // subfields to be at the top-level
    $element['wrapper_prefix']['#markup'] = '<div class="' . implode(' ', $classes) . '">';

    $proportions = $this->getSettings()['proportions'];
    /** @var \Drupal\custom_field\Plugin\CustomFieldTypeInterface $customitem */
    foreach ($this->getCustomFieldItems() as $name => $customitem) {
      $element[$name] = $customitem->widget($items, $delta, $element, $form, $form_state);
      //$element[$name]['#attributes']['class'][] = 'customfield-inline__field';
      $element[$name]['#wrapper_attributes']['class'][] = 'customfield-inline__item';
      if ($this->getSetting('customize') && isset($proportions[$name])) {
        $element[$name]['#wrapper_attributes']['class'][] = 'customfield-inline__item--' . $proportions[$name];
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
   * The options for proportions.
   */
  public function proportionOptions($option = NULL) {
    $options = [
      'one' => t('One'),
      'two' => t('Two'),
      'three' => t('Three'),
      'four' => t('Four'),
    ];
    if (!is_null($option)) {
      return isset($options[$option]) ? $options[$option] : '';
    }

    return $options;
  }

  /**
   * The options for proportions.
   */
  public function breakpointOptions($option = NULL) {
    $options = [
      '' => t('Don\'t stack'),
      'medium' => t('Medium (less than 769px)'),
      'small' => t('Small (less than 601px)'),
    ];
    if (!is_null($option)) {
      return isset($options[$option]) ? $options[$option] : '';
    }

    return $options;
  }

}
