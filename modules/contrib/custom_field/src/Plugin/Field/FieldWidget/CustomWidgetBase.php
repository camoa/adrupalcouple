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

/**
 * Base widget definition for custom field type.
 */
abstract class CustomWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The custom field type manager.
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
   * Constructs a new CustomFieldWidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_type_manager
   *   An instance of the customFieldTypeManager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CustomFieldTypeManagerInterface $custom_field_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->customFieldManager = $custom_field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Inject our custom_field plugin manager to this plugin's constructor.
    // Made possible with ContainerFactoryPluginInterface.
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

    $elements = parent::settingsForm($form, $form_state);
    $elements['#tree'] = TRUE;

    $elements['label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show field label?'),
      '#default_value' => $this->getSetting('label'),
    ];
    $elements['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper'),
      '#default_value' => $this->getSetting('wrapper'),
      '#options' => [
        'div' => $this->t('Default'),
        'fieldset' => $this->t('Fieldset'),
        'details' => $this->t('Details'),
      ],
      '#states' => [
        'visible' => [
          'input[name="fields[' . $definition->getName() . '][settings_edit_form][settings][label]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $elements['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show open by default?'),
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

    $summary[] = $this->t('Show field label?: @label', ['@label' => $this->getSetting('label') ? 'Yes' : 'No']);
    $summary[] = $this->t('Wrapper: @wrapper', ['@wrapper' => $this->getSetting('wrapper')]);
    if ($this->getSetting('wrapper') === 'details') {
      $summary[] = $this->t('Open: @open', ['@open' => $this->getSetting('open') ? 'Yes' : 'No']);
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
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition.
   */
  public function getFieldStorageDefinition(): FieldStorageDefinitionInterface {
    return $this->fieldDefinition->getFieldStorageDefinition();
  }

  /**
   * Get the custom field items for this field.
   *
   * @return \Drupal\custom_field\Plugin\CustomFieldTypeInterface[]
   *   An array of custom field items.
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

            case 'uri':
              $uri = trim($field_value);
              if ($uri === '') {
                $value[$name] = NULL;
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
    $parents = $form['#parents'];
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $processed_flag = "custom_field_{$field_name}_processed";

    // If we're using unlimited cardinality we don't display one empty item.
    // Form validation will kick in if left empty which essentially means
    // people won't be able to submit without filling required fields for
    // another value.
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && count($items) > 0 && !$form_state->get($processed_flag)) {
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      --$field_state['items_count'];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);

      // Set a flag on the form denoting that we've already removed the empty
      // item that is usually appended to the end on fresh form loads.
      $form_state->set($processed_flag, TRUE);
    }

    return parent::formMultipleElements($items, $form, $form_state);
  }

}
