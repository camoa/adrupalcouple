<?php

namespace Drupal\Tests\brevo\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\brevo\BrevoHandlerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for Brevo functional tests.
 */
abstract class BrevoFunctionalTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['brevo'];

  /**
   * Permissions required by the user to perform the tests.
   *
   * @var array
   */
  protected $permissions = [
    'administer brevo',
  ];

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $brevoConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->brevoConfig = $this->config(BrevoHandlerInterface::CONFIG_NAME);
  }

}
