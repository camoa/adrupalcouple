<?php

namespace Drupal\custom_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class CustomWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The custom field manager
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $customFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'label' => TRUE,
      'wrapper' => 'div',
      'open' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @param array $customfield_manager
   *   The CustomField Plugin Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CustomFieldTypeManagerInterface $customfield_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->customFieldManager = $customfield_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Inject our customfield plugin manager to this plugin's constructor.
    // Made possible with ContainerFactoryPluginInterface
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.customfield_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $definition = $this->fieldDefinition;

    $elements = [];
    $elements['#tree'] = TRUE;

    $elements['label'] = [
      '#type' => 'checkbox',
      '#title' => t('Show field label?'),
      '#default_value' => $this->getSetting('label'),
    ];
    $elements['wrapper'] = [
      '#type' => 'select',
      '#title' => t('Wrapper'),
      '#default_value' => $this->getSetting('wrapper'),
      '#options' => [
        'div' => t('Default'),
        'fieldset' => t('Fieldset'),
        'details' => t('Details'),
      ],
      '#states' => [
        'visible' => [
          'input[name="fields[' . $definition->getName() . '][settings_edit_form][settings][label]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['open'] = [
      '#type' => 'checkbox',
      '#title' => t('Show open by default?'),
      '#default_value' => $this->getSetting('open'),
      '#states' => [
        'visible' => [
          'input[name="fields[' . $definition->getName() . '][settings_edit_form][settings][label]"]' => ['checked' => TRUE],
          'select[name="fields[' . $definition->getName() . '][settings_edit_form][settings][wrapper]"]' => ['value' => 'details'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = t('Show field label?: @label', ['@label' => $this->getSetting('label') ? 'Yes' : 'No']);
    $summary[] = t('Wrapper: @wrapper', ['@wrapper' => $this->getSetting('wrapper')]);
    if ($this->getSetting('wrapper') === 'details') {
      $summary[] = t('Open: @open', ['@open' => $this->getSetting('open') ? 'Yes' : 'No']);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    if ($this->getSetting('label')) {
      switch ($this->getSetting('wrapper')) {
        case 'fieldset':
          $element['#type'] = 'fieldset';
          break;
        case 'details':
          $element['#type'] = 'details';
          $element['#open'] = $this->getSetting('open');
          break;
        default:
          $element['#type'] = 'item';
      }
    }

    return $element;
  }

  /**
   * Get the field storage definition.
   */
  public function getFieldStorageDefinition(): FieldStorageDefinitionInterface {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * Get the custom field items for this field.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   */
  public function getCustomFieldItems(): array {
    return $this->customFieldManager->getCustomFieldItems($this->fieldDefinition->getSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $columns = $this->getFieldSetting('columns');
    foreach ($values as &$value) {
      foreach ($value as $name => $field_value) {
        if (isset($columns[$name])) {
          switch ($columns[$name]['type']) {
            // Set value numeric values to NULL when invalid to avoid errors.
            case 'integer':
              if (!is_numeric($field_value) || intval($field_value) != $field_value || $columns[$name]['unsigned'] && $field_value < 0) {
                $value[$name] = NULL;
              }
              break;
            case 'float':
            case 'decimal':
              if (!is_numeric($field_value)) {
                $value[$name] = NULL;
              }
              break;
            case 'string_long':
              // If text field is formatted, the value is an array.
              if (is_array($field_value)) {
                if ($field_value['value'] === '') {
                  $value[$name] = NULL;
                }
                else {
                  $processed = check_markup($field_value['value'], $field_value['format']);
                  $value[$name] = $processed;
                }
              }
              else {
                $trimmed = trim($field_value);
                if ($trimmed === '') {
                  $value[$name] = NULL;
                }
              }
              break;
          }
        }
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);

    // If we're using unlimited cardinality we don't display one empty item.
    // Form validation will kick in if left empty which essentially means
    // people won't be able to submit without filling required fields for
    // another value.
    if (!$form_state->isSubmitted() && $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $element['#max_delta'] > 0) {
      $max = $element['#max_delta'];
      unset($element[$max]);
      $element['#max_delta'] = $max - 1;
      $items->removeItem($max);
      // Decrement the items count.
      $field_name = $element['#field_name'];
      $parents = $element[0]['#field_parents'];
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      $field_state['items_count']--;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    return $element;
  }

}
