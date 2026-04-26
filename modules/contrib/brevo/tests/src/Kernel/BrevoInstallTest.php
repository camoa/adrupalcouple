<?php

namespace Drupal\Tests\brevo\Kernel;

/**
 * Tests the Brevo module installation.
 *
 * @group brevo
 */
class BrevoInstallTest extends BrevoKernelTestBase {

  /**
   * Tests that the module installs correctly.
   */
  public function testModuleInstallation(): void {
    // Verify the module is enabled.
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('brevo'),
      'The brevo module is installed.'
    );
  }

  /**
   * Tests that the configuration is installed correctly.
   */
  public function testConfigurationInstallation(): void {
    $config = $this->container->get('config.factory')->get('brevo.settings');

    // Verify default configuration values.
    $this->assertSame('', $config->get('api_key'));
    $this->assertFalse($config->get('activate_marketing_automation'));
    $this->assertSame('', $config->get('client_key'));
  }

  /**
   * Tests that the Brevo services are available.
   */
  public function testServicesAvailability(): void {
    // Verify the Brevo factory service is available.
    $this->assertTrue(
      $this->container->has('brevo.brevo_client_factory'),
      'The brevo.brevo_client_factory service is available.'
    );

    // Verify the Brevo handler service is available.
    $this->assertTrue(
      $this->container->has('brevo.handler'),
      'The brevo.handler service is available.'
    );

    // Verify the logger channel is available.
    $this->assertTrue(
      $this->container->has('logger.channel.brevo'),
      'The logger.channel.brevo service is available.'
    );
  }

  /**
   * Tests that the Brevo factory can be instantiated.
   */
  public function testBrevoFactoryInstantiation(): void {
    $factory = $this->container->get('brevo.brevo_client_factory');
    $this->assertInstanceOf(
      'Drupal\brevo\BrevoFactory',
      $factory,
      'The Brevo factory is correctly instantiated.'
    );
  }

  /**
   * Tests that the Brevo handler can be instantiated.
   */
  public function testBrevoHandlerInstantiation(): void {
    $handler = $this->container->get('brevo.handler');
    $this->assertInstanceOf(
      'Drupal\brevo\BrevoHandler',
      $handler,
      'The Brevo handler is correctly instantiated.'
    );
  }

}
