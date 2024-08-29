<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'map_table' custom field formatter.
 *
 * @FieldFormatter(
 *   id = "map_table",
 *   label = @Translation("Table"),
 *   field_types = {
 *     "map",
 *   }
 * )
 */
class MapTableFormatter implements CustomFieldFormatterInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static();
    $plugin->renderer = $container->get('renderer');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'key_label' => 'Key',
      'value_label' => 'Value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $settings += self::defaultSettings();
    $elements['key_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key label'),
      '#description' => $this->t('The table header label for key column'),
      '#default_value' => $settings['key_label'],
      '#maxlength' => 128,
    ];
    $elements['value_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value label'),
      '#description' => $this->t('The table header label for value column'),
      '#default_value' => $settings['value_label'],
      '#maxlength' => 128,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(array $settings) {
    $formatter_settings = $settings['formatter_settings'] ?? self::defaultSettings();
    $values = $settings['value'];

    if (!is_array($values) || empty($values)) {
      return NULL;
    }

    $rows = [];
    foreach ($values as $mapping) {
      $rows[] = [
        $mapping['key'],
        $mapping['value'],
      ];
    }
    $build = [
      '#type' => 'table',
      '#header' => [
        'key' => $formatter_settings['key_label'],
        'value' => $formatter_settings['value_label'],
      ],
      '#rows' => $rows,
    ];

    return $this->renderer->render($build);
  }

}
