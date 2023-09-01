<?php

namespace Drupal\custom_field\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
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
              $values[$name] = is_string($value) && filter_var($value, FILTER_VALIDATE_URL) ? $value : NULL;
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
        $this->messenger()->addError($e->getMessage());

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
    if (substr($color, 0, 1) === '#') {
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

}
