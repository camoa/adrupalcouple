<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 *
 * @FieldFormatter(
 *   id = "timestamp_ago",
 *   label = @Translation("Time ago"),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class TimestampAgoFormatter implements CustomFieldFormatterInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

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
    $plugin->dateFormatter = $container->get('date.formatter');
    $plugin->renderer = $container->get('renderer');
    $plugin->request = $container->get('request_stack')->getCurrentRequest();

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'future_format' => '@interval hence',
      'past_format' => '@interval ago',
      'granularity' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $settings += static::defaultSettings();

    $elements['future_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Future format'),
      '#default_value' => $settings['future_format'],
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $elements['past_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Past format'),
      '#default_value' => $settings['past_format'],
      '#description' => $this->t('Use <em>@interval</em> where you want the formatted interval text to appear.'),
    ];

    $elements['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('How many time interval units should be shown in the formatted output.'),
      '#default_value' => $settings['granularity'] ?: 2,
      '#min' => 1,
      '#max' => 6,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(array $settings) {
    $formatter_settings = $settings['formatter_settings'] ?? self::defaultSettings();
    $timestamp = $settings['value'];
    $build = $this->formatTimestamp($timestamp, $formatter_settings);

    return $this->renderer->render($build);
  }

  /**
   * Formats a timestamp.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   * @param array $settings
   *   The formatter settings.
   *
   * @return array
   *   The formatted timestamp string using the past or future format setting.
   */
  protected function formatTimestamp($timestamp, array $settings) {
    $granularity = $settings['granularity'];
    $options = [
      'granularity' => $granularity,
      'return_as_object' => TRUE,
    ];

    if ($this->request->server->get('REQUEST_TIME') > $timestamp) {
      $result = $this->dateFormatter->formatTimeDiffSince($timestamp, $options);
      $build = [
        '#markup' => new FormattableMarkup($settings['past_format'], ['@interval' => $result->getString()]),
      ];
    }
    else {
      $result = $this->dateFormatter->formatTimeDiffUntil($timestamp, $options);
      $build = [
        '#markup' => new FormattableMarkup($settings['future_format'], ['@interval' => $result->getString()]),
      ];
    }
    CacheableMetadata::createFromObject($result)->applyTo($build);
    return $build;
  }

}
