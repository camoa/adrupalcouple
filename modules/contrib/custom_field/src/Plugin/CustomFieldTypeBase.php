<?php

namespace Drupal\custom_field\Plugin;

use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for CustomField Type plugins.
 */
abstract class CustomFieldTypeBase extends PluginBase implements CustomFieldTypeInterface {
  use StringTranslationTrait;
  /**
   * The name of the custom field item.
   *
   * @var string
   */
  protected $name = 'value';

  /**
   * The data type of the custom field item.
   *
   * @var string
   */
  protected $data_type = '';

  /**
   * The max length of the custom field item database column.
   *
   * @var integer
   */
  protected $max_length = 255;

  /**
   * A boolean to determine if a custom field type of integer is unsigned.
   *
   * @var boolean
   */
  protected $unsigned = FALSE;

  /**
   * An array of widget settings.
   *
   * @var array
   */
  protected $widget_settings = [];

  /**
   * An array of formatter settings.
   *
   * @var array
   */
  protected $formatter_settings = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultWidgetSettings(): array {
    return [
      'label' => '',
      'settings' => [
        'description' => '',
        'description_display' => 'after',
        'required' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFormatterSettings(): array {
    return [];
  }

  /**
   * Construct a CustomFieldType plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Initialize properties based on configuration.
    $this->name = $this->configuration['name'] ?? 'value';
    $this->max_length = $this->configuration['max_length'] ?? 255;
    $this->unsigned = $this->configuration['unsigned'] ?? FALSE;
    $this->widget_settings = $this->configuration['widget_settings'] ?? [];
    $this->formatter_settings = $this->configuration['formatter_settings'] ?? [];
    $this->data_type = $this->configuration['data_type'] ?? '';

    // We want to default the label to the column name, so we do that before the
    // merge and only if it's unset since a value of '' may be what the user
    // wants for no label
    if (!isset($this->widget_settings['label'])) {
      $this->widget_settings['label'] = ucfirst(str_replace(['-', '_'], ' ', $this->name));
    }

    // Merge defaults
    $this->widget_settings = $this->widget_settings + self::defaultWidgetSettings();
    $this->formatter_settings = $this->formatter_settings + self::defaultFormatterSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Prep the element base properties. Implementations of the plugin can
    // override as necessary or just set #type and be on their merry way.
    $settings = $this->widget_settings['settings'];
    $is_required = $items->getFieldDefinition()->isRequired();
    $item = $items[$delta];
    return [
      '#title' => $this->widget_settings['label'],
      '#description' => $settings['description'] ?: NULL,
      '#description_display' => $settings['description_display'] ?: NULL,
      '#default_value' => $item->{$this->name} ?? NULL,
      '#required' => !($form_state->getBuildInfo()['base_form_id'] == 'field_config_form') && $is_required && $settings['required'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->widget_settings['settings'];

    // Some table columns containing raw markup.
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->widget_settings['label'],
      '#required' => TRUE,
    ];
    $element['settings'] = [
      '#type' => 'details',
      '#title' => t('Settings'),
    ];

    // Keep settings open during ajax updates.
    if ($form_state->isRebuilding()) {
      $trigger = $form_state->getTriggeringElement();
      $parents = $trigger['#parents'];
      if (in_array($this->getName(), $parents)) {
        $element['settings']['#open'] = TRUE;
      }
    }

    // Some table columns containing raw markup.
    $element['settings']['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#description' => t('This setting is only applicable when the field itself is required.'),
      '#default_value' => $settings['required'],
    ];

    // Some table columns containing raw markup.
    $element['settings']['description'] = [
      '#type' => 'textarea',
      '#title' => t('Help text'),
      '#description' => t('Instructions to present to the user below this field on the editing form.'),
      '#rows' => 2,
      '#default_value' => $settings['description'],
    ];

    $element['settings']['description_display'] = [
      '#type' => 'radios',
      '#title' => t('Help text position'),
      '#options' => [
        'before' => t('Before input'),
        'after' => t('After input'),
      ],
      '#default_value' => $settings['description_display'],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatterSettingsForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function value(CustomItem $item) {
    return $item->{$this->name};
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->widget_settings['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSetting(string $name): array {
    return $this->widget_settings[$name] ?? static::defaultWidgetSettings()[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatterSetting(string $name) {
    return $this->formatter_settings[$name] ?? static::defaultFormatterSettings()[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings(): array {
    return $this->widget_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatterSettings(): array {
    return $this->formatter_settings;
  }
}
