<?php

namespace Drupal\Tests\brevo_mailer\Kernel;

/**
 * Tests the Brevo Mailer module installation.
 *
 * @group brevo_mailer
 */
class BrevoMailerInstallTest extends BrevoKernelTestBase {

  /**
   * Tests that the module installs correctly.
   */
  public function testModuleInstallation(): void {
    // Verify the module is enabled.
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('brevo_mailer'),
      'The brevo_mailer module is installed.'
    );

    // Verify the parent module is also enabled.
    $this->assertTrue(
      $this->container->get('module_handler')->moduleExists('brevo'),
      'The brevo module is installed as a dependency.'
    );
  }

  /**
   * Tests that the configuration is installed correctly.
   */
  public function testConfigurationInstallation(): void {
    $config = $this->container->get('config.factory')->get('brevo_mailer.settings');

    // Verify default configuration values.
    $this->assertFalse($config->get('debug_mode'));
    $this->assertFalse($config->get('test_mode'));
    $this->assertSame('plain_text', $config->get('format_filter'));
    $this->assertFalse($config->get('use_queue'));
    $this->assertFalse($config->get('use_theme'));
  }

  /**
   * Tests that the Brevo Mailer services are available.
   */
  public function testServicesAvailability(): void {
    // Verify the mail handler service is available.
    $this->assertTrue(
      $this->container->has('brevo_mailer.mail_handler'),
      'The brevo_mailer.mail_handler service is available.'
    );

    // Verify the event subscriber service is available.
    $this->assertTrue(
      $this->container->has('brevo_mailer.event_subscriber'),
      'The brevo_mailer.event_subscriber service is available.'
    );

    // Verify the logger channel is available.
    $this->assertTrue(
      $this->container->has('logger.channel.brevo_mailer'),
      'The logger.channel.brevo_mailer service is available.'
    );
  }

  /**
   * Tests that the mail handler can be instantiated.
   */
  public function testMailHandlerInstantiation(): void {
    $handler = $this->container->get('brevo_mailer.mail_handler');
    $this->assertInstanceOf(
      'Drupal\brevo_mailer\BrevoMailerHandler',
      $handler,
      'The Brevo Mailer handler is correctly instantiated.'
    );
  }

}
