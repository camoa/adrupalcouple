<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base formatter for custom_field.
 */
abstract class CustomFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Base class for custom_field formatter plugins.
   *
   * @var array|\Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface|null
   */
  protected $customFieldManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [] + parent::defaultSettings();
  }

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
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CustomFieldTypeManagerInterface $custom_field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->customFieldManager = $custom_field_manager;
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
      $container->get('plugin.manager.customfield_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
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

}
