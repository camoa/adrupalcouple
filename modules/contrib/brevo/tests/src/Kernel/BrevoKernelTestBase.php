<?php

namespace Drupal\Tests\brevo\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\brevo\BrevoHandlerInterface;

/**
 * Brevo kernel test base class.
 */
abstract class BrevoKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['brevo'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['brevo']);
  }

  /**
   * Sets the Brevo configuration value.
   *
   * @param string $config_name
   *   The config key name.
   * @param string $config_value
   *   The config key value.
   */
  protected function setConfigValue($config_name, $config_value) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable(BrevoHandlerInterface::CONFIG_NAME)
      ->set($config_name, $config_value)
      ->save();
  }

}
