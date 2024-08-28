<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for testing profiles with Config Overlay.
 */
abstract class ConfigOverlayTestBase extends BrowserTestBase {

  use ConfigOverlayTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_overlay'];

  /**
   * A list of collections for this test's configuration.
   *
   * @var string[]
   */
  protected array $collections = [StorageInterface::DEFAULT_COLLECTION];

}
