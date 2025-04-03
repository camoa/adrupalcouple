<?php

namespace Drupal\Tests\brevo_mailer\Kernel;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Brevo kernel test base class.
 */
abstract class BrevoKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['brevo', 'brevo_mailer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['brevo']);
    $this->installConfig(['brevo_mailer']);
  }

  /**
   * Sets the Brevo Mailer configuration value.
   *
   * @param string $config_name
   *   The config key name.
   * @param string $config_value
   *   The config key value.
   */
  protected function setConfigValue($config_name, $config_value) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable(BrevoMailerHandlerInterface::CONFIG_NAME)
      ->set($config_name, $config_value)
      ->save();
  }

}
