<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\Integration;

/**
 * Tests installing with Config Ignore, Config Overlay and Config Split.
 *
 * Config Overlay's behavior is slightly altered when Config Split is installed,
 * so this extends ConfigIgnoreTest to make sure that having all three modules
 * enabled does not break functionality.
 *
 * @group config_overlay
 */
class ConfigIgnoreConfigSplitTest extends ConfigIgnoreTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_split'];

}
