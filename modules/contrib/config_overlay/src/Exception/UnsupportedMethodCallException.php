<?php

declare(strict_types=1);

namespace Drupal\config_overlay\Exception;

/**
 * Thrown when calling an unsupported method on a ConfigStorage object.
 */
class UnsupportedMethodCallException extends \BadMethodCallException {
}
