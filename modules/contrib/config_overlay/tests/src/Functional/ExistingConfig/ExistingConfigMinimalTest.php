<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

/**
 * Tests installation of the Minimal profile from existing configuration.
 *
 * @group config_overlay
 */
class ExistingConfigMinimalTest extends ExistingConfigProfileTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
