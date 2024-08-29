<?php

namespace Drupal\custom_field\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

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
  protected $dataType = '';

  /**
   * The max length of the custom field item database column.
   *
   * @var int
   */
  protected $maxLength = 255;

  /**
   * A boolean to determine if a custom field type of integer is unsigned.
   *
   * @var bool
   */
  protected $unsigned = FALSE;

  /**
   * An array of widget settings.
   *
   * @var array
   */
  protected $widgetSettings = [];

  /**
   * Should this field item be included in the empty check?
   *
   * @var bool
   */
  protected $checkEmpty = FALSE;

  /**
   * Returns the 'scale' field storage value.
   *
   * @var int|mixed
   */
  protected $scale;

  /**
   * Returns the 'datetime_type' field storage value.
   *
   * @var string
   */
  protected $datetimeType;

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
   * Construct a CustomFieldType plugin instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Initialize properties based on configuration.
    $this->name = $this->configuration['name'] ?? 'value';
    $this->maxLength = $this->configuration['max_length'] ?? 255;
    $this->unsigned = $this->configuration['unsigned'] ?? FALSE;
    $this->widgetSettings = $this->configuration['widget_settings'] ?? [];
    $this->dataType = $this->configuration['data_type'] ?? '';
    $this->checkEmpty = $this->configuration['check_empty'] ?? FALSE;
    $this->scale = $this->configuration['scale'] ?? 2;
    $this->datetimeType = $this->configuration['datetime_type'] ?? static::DATETIME_TYPE_DATETIME;

    // We want to default the label to the column name, so we do that before the
    // merge and only if it's unset since a value of '' may be what the user
    // wants for no label.
    if (!isset($this->widgetSettings['label'])) {
      $this->widgetSettings['label'] = ucfirst(str_replace(['-', '_'], ' ', $this->name));
    }

    // Merge defaults.
    $this->widgetSettings = $this->widgetSettings + self::defaultWidgetSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function widget(FieldItemListInterface $items, int $delta, array $element, array &$form, FormStateInterface $form_state): array {
    // Prep the element base properties. Implementations of the plugin can
    // override as necessary or just set #type and be on their merry way.
    $settings = $this->widgetSettings['settings'];
    $is_required = $items->getFieldDefinition()->isRequired();
    $item = $items[$delta];
    return [
      '#title' => $this->widgetSettings['label'],
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
    $settings = $this->widgetSettings['settings'];

    // Some table columns containing raw markup.
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->widgetSettings['label'],
      '#required' => TRUE,
    ];
    $element['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
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
      '#title' => $this->t('Required'),
      '#description' => $this->t('This setting is only applicable when the field itself is required.'),
      '#default_value' => $settings['required'],
    ];

    // Some table columns containing raw markup.
    $element['settings']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.'),
      '#rows' => 2,
      '#default_value' => $settings['description'],
    ];

    $element['settings']['description_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Help text position'),
      '#options' => [
        'before' => $this->t('Before input'),
        'after' => $this->t('After input'),
      ],
      '#default_value' => $settings['description_display'],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function value(FieldItemInterface $item): mixed {
    return $item->{$this->name};
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormatter(): string {
    return $this->getPluginDefinition()['default_formatter'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->widgetSettings['label'];
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
  public function getMaxlength(): int {
    return $this->maxLength;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType(): string {
    return $this->dataType;
  }

  /**
   * {@inheritdoc}
   */
  public function isUnsigned(): bool {
    return $this->unsigned;
  }

  /**
   * {@inheritdoc}
   */
  public function getScale(): int {
    return $this->scale;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatetimeType(): string {
    return $this->datetimeType;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSetting(string $name): array {
    return $this->widgetSettings[$name] ?? static::defaultWidgetSettings()[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSettings(): array {
    return $this->widgetSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEmpty(): bool {
    return $this->checkEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(array $settings): array {
    return [
      'type' => 'varchar',
      'length' => $settings['max_length'] ?? 255,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(array $settings): mixed {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints(array $settings): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(FieldItemInterface $item) {
    return Url::fromUri($item->{$this->name});
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal(FieldItemInterface $item) {
    return $this->getUrl($item)->isExternal();
  }

}
