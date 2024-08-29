<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for 'DateTime custom field formatter' plugin implementations.
 */
abstract class DateTimeFormatterBase implements CustomFieldFormatterInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

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
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    $plugin->renderer = $container->get('renderer');
    $plugin->dateFormatter = $container->get('date.formatter');
    $plugin->dateFormatStorage = $container->get('entity_type.manager')->getStorage('date_format');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'timezone_override' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $settings += static::defaultSettings();
    $elements['timezone_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone override'),
      '#description' => $this->t('The time zone selected here will always be used'),
      '#options' => TimeZoneFormHelper::getOptionsListByRegion(TRUE),
      '#default_value' => $settings['timezone_override'],
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
    $formatter_settings = $settings['formatter_settings'] ?? static::defaultSettings();
    $datetime_type = $settings['configuration']['datetime_type'];

    /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
    $date = $this->getDate($settings['value'], $datetime_type);

    if ($date === NULL) {
      return NULL;
    }

    $build = $this->buildDateWithIsoAttribute($date, $datetime_type, $formatter_settings);

    return $this->renderer->render($build);
  }

  /**
   * Helper function to convert stored value to date object.
   *
   * @param string $value
   *   The storage value as string.
   * @param string $datetime_type
   *   The date type.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Return a date object or null.
   */
  protected function getDate(string $value, string $datetime_type) {
    $storage_format = $datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE ? CustomFieldTypeInterface::DATE_STORAGE_FORMAT : CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT;
    $date_object = NULL;
    try {
      $date = DrupalDateTime::createFromFormat($storage_format, $value, CustomFieldTypeInterface::STORAGE_TIMEZONE);
      if ($date instanceof DrupalDateTime && !$date->hasErrors()) {
        $date_object = $date;
        // If the format did not include an explicit time portion, then the
        // time will be set from the current time instead. For consistency, we
        // set the time to 12:00:00 UTC for date-only fields. This is used so
        // that the local date portion is the same, across nearly all time
        // zones.
        // @see \Drupal\Component\Datetime\DateTimePlus::setDefaultDateTime()
        // @see http://php.net/manual/datetime.createfromformat.php
        if ($datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
          $date_object->setDefaultDateTime();
        }
      }
    }
    catch (\Exception $e) {
      // @todo Handle this.
    }
    return $date_object;
  }

  /**
   * Creates a formatted date value as a string.
   *
   * @param object $date
   *   A date object.
   * @param array $settings
   *   The formatter settings.
   *
   * @return string
   *   A formatted date string using the chosen format.
   */
  abstract protected function formatDate(object $date, array $settings): string;

  /**
   * Sets the proper time zone on a DrupalDateTime object for the current user.
   *
   * A DrupalDateTime object loaded from the database will have the UTC time
   * zone applied to it.  This method will apply the time zone for the current
   * user, based on system and user settings.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A DrupalDateTime object.
   * @param string $datetime_type
   *   The date type.
   */
  protected function setTimeZone(DrupalDateTime $date, string $datetime_type) {
    if ($datetime_type === CustomFieldTypeInterface::DATETIME_TYPE_DATE) {
      // A date without time has no timezone conversion.
      $timezone = CustomFieldTypeInterface::STORAGE_TIMEZONE;
    }
    else {
      $timezone = date_default_timezone_get();
    }
    $date->setTimeZone(timezone_open($timezone));
  }

  /**
   * Creates a render array from a date object.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   * @param string $datetime_type
   *   The date type.
   * @param array $settings
   *   The formatter settings.
   *
   * @return array
   *   A render array.
   */
  protected function buildDate(DrupalDateTime $date, string $datetime_type, array $settings) {
    $this->setTimeZone($date, $datetime_type);

    $build = [
      '#markup' => $this->formatDate($date, $settings),
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Creates a render array from a date object with ISO date attribute.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A date object.
   * @param string $datetime_type
   *   The date type.
   * @param array $settings
   *   The formatter settings.
   *
   * @return array
   *   A render array.
   */
  protected function buildDateWithIsoAttribute(DrupalDateTime $date, string $datetime_type, array $settings): array {
    // Create the ISO date in Universal Time.
    $iso_date = $date->format("Y-m-d\TH:i:s") . 'Z';

    $this->setTimeZone($date, $datetime_type);

    $build = [
      '#theme' => 'time',
      '#text' => $this->formatDate($date, $settings),
      '#attributes' => [
        'datetime' => $iso_date,
      ],
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
    ];

    return $build;
  }

}
