<?php

namespace Drupal\custom_field\Feeds\Target;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a custom field mapper.
 *
 * @FeedsTarget(
 *   id = "custom_field_feeds_target",
 *   field_types = {"custom"}
 * )
 */
class CustomField extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + ['timezone' => 'UTC'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone handling'),
      '#options' => $this->getTimezoneOptions(),
      '#default_value' => $this->configuration['timezone'],
      '#description' => $this->t('This value will only be used if the timezone is missing.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = parent::getSummary();

    $options = $this->getTimezoneOptions();

    $summary[] = $this->t('Default timezone: %zone', [
      '%zone' => $options[$this->configuration['timezone']],
    ]);

    return $summary;
  }

  /**
   * Returns the timezone options.
   *
   * @return array
   *   A map of timezone options.
   */
  public function getTimezoneOptions() {
    return [
      '__SITE__' => $this->t('Site default'),
    ] + system_time_zones();
  }

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition);
    $columns = $field_definition->getSetting('columns');
    foreach ($columns as $name => $column) {
      // There's no use case for mapping to uuid.
      if ($column['type'] === 'uuid') {
        continue;
      }
      $definition->addProperty($name);
      $unique_types = [
        'string',
        'string_long',
        'integer',
        'decimal',
        'email',
        'uri',
        'telephone',
      ];
      if (in_array($column['type'], $unique_types)) {
        $definition->markPropertyUnique($name);
      }
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (!empty($values)) {
      $columns = $this->settings['columns'];
      foreach ($values as $name => $v) {
        if (isset($columns[$name])) {
          $value = is_string($v) ? trim($v) : $v;
          switch ($columns[$name]['type']) {
            case 'boolean':
              $values[$name] = !is_null($value) ? $this->convertBoolean($value) : NULL;
              break;

            case 'email':
              $values[$name] = is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : NULL;
              break;

            case 'integer':
              $values[$name] = is_numeric($value) ? (int) $value : NULL;
              break;

            case 'decimal':
            case 'float':
              $values[$name] = is_numeric($value) ? $value : NULL;
              break;

            case 'map':
              $values[$name] = isset($value) ? $this->convertMap($value) : NULL;
              break;

            case 'color':
              $values[$name] = is_string($value) ? $this->convertColor($value) : NULL;
              break;

            case 'uri':
              $values[$name] = is_string($value) ? $this->convertUrl($value) : NULL;
              break;

            case 'datetime':
              $datetime_type = $columns[$name]['datetime_type'];
              $storage_format = $datetime_type === 'date' ? CustomFieldTypeInterface::DATE_STORAGE_FORMAT : CustomFieldTypeInterface::DATETIME_STORAGE_FORMAT;
              $values[$name] = isset($value) ? $this->convertDateTime((string) $value, $storage_format) : NULL;
              break;

            default:
              $values[$name] = (string) $value;
          }
        }
      }
      return $values;
    }
    else {
      throw new EmptyFeedException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values): array {
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        $this->addMessage($e->getFormattedMessage(), 'error');
      }
    }
    return $return;
  }

  /**
   * Converts the given value to a boolean.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return bool
   *   The value, converted to a boolean.
   */
  protected function convertBoolean(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }
    if (is_string($value)) {
      return (bool) trim($value);
    }
    if (is_scalar($value)) {
      return (bool) $value;
    }
    if (empty($value)) {
      return FALSE;
    }
    if (is_array($value)) {
      $value = current($value);
      return $this->convertBoolean($value);
    }

    $value = @(string) $value;
    return $this->convertBoolean($value);
  }

  /**
   * Converts the given value to a boolean.
   *
   * @param string $color
   *   The value to convert.
   *
   * @return string|null
   *   The value, converted to a hexadecimal or NULL.
   */
  protected function convertColor(string $color): ?string {
    if (str_starts_with($color, '#')) {
      $color = substr($color, 1);
    }

    $length = strlen($color);

    // Account for hexadecimal short notation.
    if ($length === 3) {
      $color .= $color;
    }

    // Make sure we have a valid hexadecimal color.
    return strlen($color) === 6 ? '#' . strtoupper($color) : NULL;
  }

  /**
   * Converts json object to an array.
   *
   * @param string $map
   *   The json string to convert.
   *
   * @return array|null
   *   The value, converted to an array or NULL.
   */
  protected function convertMap(string $map): ?array {
    $decoded = json_decode($map, TRUE);
    if (is_array($decoded)) {
      return $decoded;
    }

    return NULL;
  }

  /**
   * Converts a value to date string or null.
   *
   * @param string $value
   *   The date value to convert.
   * @param string $format
   *   The date format.
   *
   * @return string|null
   *   A formatted date, in UTC time or NULL.
   */
  protected function convertDateTime(string $value, string $format): ?string {
    $date = $this->convertDate($value);

    if (isset($date) && !$date->hasErrors()) {
      return $date->format($format, [
        'timezone' => CustomFieldTypeInterface::STORAGE_TIMEZONE,
      ]);
    }

    return NULL;
  }

  /**
   * Converts a value to Date object or null.
   *
   * @param string $value
   *   The date value to convert.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   A datetime object or null, if there is no value or if the date value
   *   has errors.
   */
  protected function convertDate(string $value): mixed {
    $value = trim($value);

    // This is a year value.
    if (ctype_digit($value) && strlen($value) === 4) {
      $value = 'January ' . $value;
    }

    if (is_numeric($value)) {
      $date = DrupalDateTime::createFromTimestamp($value, $this->getTimezoneConfiguration());
    }

    elseif (strtotime($value)) {
      $date = new DrupalDateTime($value, $this->getTimezoneConfiguration());
    }

    if (isset($date) && !$date->hasErrors()) {
      return $date;
    }

    return NULL;
  }

  /**
   * Converts a value to valid Url or null.
   *
   * @param string $url
   *   The uri string to evaluate and convert.
   *
   * @return string|null
   *   The url if valid, otherwise NULL.
   */
  protected function convertUrl(string $url): ?string {
    // Support linking to nothing.
    if (in_array($url, ['<nolink>', '<none>'], TRUE)) {
      $url = 'route:' . $url;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($url) && parse_url($url, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($url, '<front>') === 0) {
        $url = '/' . substr($url, strlen('<front>'));
      }
      // Prepend only with 'internal:' if the uri starts with '/', '?' or '#'.
      if (in_array($url[0], ['/', '?', '#'], TRUE)) {
        $url = 'internal:' . $url;
      }
    }
    // Test for valid url.
    elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
      return NULL;
    }

    return $url;
  }

  /**
   * Returns the timezone configuration.
   */
  public function getTimezoneConfiguration() {
    return ($this->configuration['timezone'] == '__SITE__') ?
      \Drupal::config('system.date')->get('timezone.default') : $this->configuration['timezone'];
  }

}
