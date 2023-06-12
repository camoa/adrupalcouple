<?php

namespace Drupal\custom_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CustomFormatterBase class.
 */
abstract class CustomFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var array|\Drupal\custom_field\Plugin\CustomFieldTypeManagerInterface|null
   */
  protected $customFieldManager = null;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @param array $custom_field_manager
   *   The CustomField Plugin Manager.
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
    // Made possible with ContainerFactoryPluginInterface
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
   */
  public function getCustomFieldItems(): array {
    return $this->customFieldManager->getCustomFieldItems($this->fieldDefinition->getSettings());
  }

}
