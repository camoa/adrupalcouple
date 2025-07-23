<?php

declare(strict_types=1);

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

  /**
   * The language to install the site in.
   *
   * @var string
   */
  protected string $langcode = 'en';

}
