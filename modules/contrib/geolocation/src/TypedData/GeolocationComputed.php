<?php

namespace Drupal\geolocation\TypedData;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\TypedData\TypedData;

/**
 * Class GeolocationComputed typed data.
 *
 * @package Drupal\geolocation
 */
class GeolocationComputed extends TypedData {

  use DependencySerializationTrait;

  /**
   * Cached processed value.
   *
   * @var string
   */
  protected string $value = '';

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (!empty($this->value)) {
      return $this->value;
    }

    /** @var \Drupal\geolocation\Plugin\Field\FieldType\GeolocationItem|null $item */
    $item = $this->getParent();

    // Ensure latitude and longitude exist.
    if ($item && !$item->isEmpty()) {
      $lat = trim($item->get('lat')->getValue());
      $lng = trim($item->get('lng')->getValue());

      // Format the returned value.
      $this->value = $lat . ', ' . $lng;
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE): void {
    $this->value = (string) $value;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
