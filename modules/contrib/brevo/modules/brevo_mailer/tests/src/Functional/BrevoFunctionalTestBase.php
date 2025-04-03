<?php

namespace Drupal\Tests\brevo_mailer\Functional;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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
  protected static $modules = ['brevo', 'brevo_mailer'];

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
  protected $brevoMailerConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->brevoMailerConfig = $this->config(BrevoMailerHandlerInterface::CONFIG_NAME);
  }

}
