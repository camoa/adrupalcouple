<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\custom_field\Plugin\CustomFieldFormatterManager;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base formatter for custom_field.
 */
abstract class BaseFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The custom field type manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface
   */
  protected CustomFieldTypeManagerInterface $customFieldManager;

  /**
   * The custom field formatter manager.
   *
   * @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager
   */
  protected CustomFieldFormatterManager $customFieldFormatterManager;

  /**
   * Constructs a CustomFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface $custom_field_manager
   *   The CustomFieldTypeManagerInterface.
   * @param \Drupal\custom_field\Plugin\CustomFieldFormatterManager $formatter_manager
   *   The CustomFieldFormatterManager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    CustomFieldTypeManagerInterface $custom_field_manager,
    CustomFieldFormatterManager $formatter_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->customFieldManager = $custom_field_manager;
    $this->customFieldFormatterManager = $formatter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Inject our customfield plugin manager to this plugin's constructor.
    // Made possible with ContainerFactoryPluginInterface.
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.custom_field_type'),
      $container->get('plugin.manager.custom_field_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'fields' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $field_settings = $this->getFieldSettings();
    $field_name = $this->fieldDefinition->getName();
    $is_views_form = $form_state->getFormObject()->getFormId() == 'views_ui_config_item_form';

    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Field settings'),
      '#open' => TRUE,
    ];

    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $settings = $this->getSetting('fields')[$name] ?? [];
      $formatter_settings = $settings['formatter_settings'] ?? [];
      $type = $customItem->getDataType();
      $formatter_options = $this->customFieldManager->getCustomFieldFormatterOptions($type);
      $definition = $customItem->getPluginDefinition();
      $widget_settings = $customItem->getWidgetSetting('settings');
      $default_format = $settings['format_type'] ?? $definition['default_formatter'];
      $trigger = $form_state->getTriggeringElement();
      $trigger_match = 'fields[' . $field_name . '][settings_edit_form][settings][fields][' . $name . '][format_type]';
      $visibility_path = 'fields[' . $field_name . '][settings_edit_form][settings][fields][' . $name . '][formatter_settings]';
      // Views config form has different field keys.
      if ($is_views_form) {
        $trigger_match = 'options[settings][fields][' . $name . '][format_type]';
        $visibility_path = 'options[settings][fields][' . $name . '][formatter_settings]';
      }

      if (!empty($trigger) && $trigger['#name'] == $trigger_match) {
        $format_type = $trigger['#value'];
      }
      else {
        $format_type = $default_format;
      }
      $form['#visibility_path'] = $visibility_path;
      $form['#storage_settings'] = $field_settings['columns'][$name];
      $form['#field_settings'] = $field_settings['field_settings'][$name] ?? [];
      $wrapper_id = 'field-' . $name . '-ajax';
      $form['fields'][$name] = [
        '#type' => 'details',
        '#title' => $this->t('@label (@type)', [
          '@label' => $customItem->getLabel(),
          '@type' => $customItem->getDataType(),
        ]),
      ];
      if (!empty($formatter_options)) {
        $form['fields'][$name]['format_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Format type'),
          '#options' => $formatter_options,
          '#default_value' => $format_type,
          '#ajax' => [
            'callback' => [static::class, 'actionCallback'],
            'wrapper' => $wrapper_id,
            'method' => 'replace',
          ],
        ];
        $form['fields'][$name]['formatter_settings'] = [
          '#type' => 'container',
          '#prefix' => '<div id="' . $wrapper_id . '">',
          '#suffix' => '</div>',
        ];
        // Get the formatter settings form.
        /** @var \Drupal\custom_field\Plugin\CustomFieldFormatterInterface $format */
        if ($format = $this->customFieldFormatterManager->createInstance($format_type)) {
          $formatter = $format->settingsForm($form, $form_state, $formatter_settings);
        }
        $form['fields'][$name]['formatter_settings'] += $formatter;
      }
      // Add label_display field to everything but checkboxes.
      if ($customItem->getDataType() !== 'boolean') {
        $form['fields'][$name]['formatter_settings']['label_display'] = [
          '#type' => 'select',
          '#title' => $this->t('Label display'),
          '#options' => $this->fieldLabelOptions(),
          '#default_value' => $formatter_settings['label_display'] ?? 'above',
          '#weight' => 10,
        ];
      }
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $custom_fields = $this->getCustomFieldItems();
    $settings = $this->getSetting('fields');
    foreach ($custom_fields as $id => $custom_field) {
      $type_plugin = $custom_field->getPluginDefinition();
      $format_type = $settings[$id]['format_type'] ?? $type_plugin['default_formatter'];
      /** @var \Drupal\custom_field\Plugin\CustomFieldFormatterManager $formatter_plugin */
      $definition = $this->customFieldFormatterManager->getDefinition($format_type);
      $field_label = $custom_field->getLabel();
      $format_label = $definition['label'];
      $formatted_summary = new FormattableMarkup(
        '<strong>@label</strong>: @format_label', [
          '@label' => $field_label,
          '@format_label' => $format_label,
        ]
      );
      $summary[] = $this->t('@summary', ['@summary' => $formatted_summary]);
    }

    return $summary;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function actionCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#array_parents'])) {
      $subformKeys = $trigger['#array_parents'];
      // Remove the triggering element itself:
      array_pop($subformKeys);
      $subformKeys[] = 'formatter_settings';
      // Return the subform:
      return NestedArray::getValue($form, $subformKeys);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
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
   * Returns an array of formatted custom field item values for a singe field.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   An array of formatted values.
   */
  protected function getFormattedValues(FieldItemInterface $item, string $langcode) {
    $settings = $this->getSetting('fields');
    $values = [];
    foreach ($this->getCustomFieldItems() as $name => $customItem) {
      $markup = $customItem->value($item);
      if ($markup === '' || $markup === NULL) {
        continue;
      }
      $format_settings = $settings[$name] ?? [];
      $format_settings += [
        'configuration' => $customItem->configuration,
        'widget_settings' => $customItem->getWidgetSetting('settings'),
        'value' => $markup,
        'langcode' => $item->getLangcode(),
      ];
      $format_type = $format_settings['format_type'] ?? $customItem->getDefaultFormatter();
      $plugin = $this->customFieldFormatterManager->createInstance($format_type);
      if (method_exists($plugin, 'formatValue')) {
        $markup = $plugin->formatValue($format_settings);
        if ($markup === '' || $markup === NULL) {
          continue;
        }
      }
      if (method_exists($plugin, 'defaultSettings')) {
        $format_settings += $plugin->defaultSettings();
      }
      $markup = [
        'name' => $name,
        'value' => [
          '#markup' => $markup,
        ],
        'label' => $customItem->getLabel(),
        'label_display' => $format_settings['formatter_settings']['label_display'] ?? 'above',
        'type' => $customItem->getPluginId(),
      ];
      $values[$name] = $markup;
    }

    return $values;
  }

  /**
   * Returns an array of visibility options for custom field labels.
   *
   * Copied from Drupal\field_ui\Form\EntityViewDisplayEditForm (can't call
   * directly since it's protected)
   *
   * @return array
   *   An array of visibility options.
   */
  protected function fieldLabelOptions(): array {
    return [
      'above' => $this->t('Above'),
      'inline' => $this->t('Inline'),
      'hidden' => '- ' . $this->t('Hidden') . ' -',
      'visually_hidden' => '- ' . $this->t('Visually hidden') . ' -',
    ];
  }

  /**
   * Returns an individual option string for custom field labels.
   *
   * @return string
   *   The string value of a specified label option.
   */
  protected function fieldLabelOption($option): string {
    return $this->fieldLabelOptions()[$option];
  }

}
