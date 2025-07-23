<?php

declare(strict_types=1);

namespace Drupal\Tests\config_overlay\Functional\ExistingConfig;

/**
 * Tests installation of the Testing profile from existing configuration.
 *
 * @group config_overlay
 */
class ExistingConfigTestingTest extends ExistingConfigProfileTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
