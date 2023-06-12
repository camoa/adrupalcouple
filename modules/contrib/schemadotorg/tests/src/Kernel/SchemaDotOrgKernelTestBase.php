<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\schemadotorg\Traits\SchemaDotOrgTestTrait;

/**
 * Defines an abstract test base for Schema.org kernel tests.
 */
abstract class SchemaDotOrgKernelTestBase extends EntityKernelTestBase {
  use SchemaDotOrgTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['schemadotorg'];

  /**
   * Convert all render(able) markup into strings.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  protected function convertMarkupToStrings(array &$elements): void {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        self::convertMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

}
