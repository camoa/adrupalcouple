<?php

namespace Drupal\Tests\brevo\Kernel;

use Brevo\Client\Api\AccountApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Drupal\brevo\BrevoFactory;

/**
 * Brevo client factory test.
 *
 * @coversDefaultClass \Drupal\brevo\BrevoFactory
 *
 * @group brevo
 */
class BrevoFactoryTest extends BrevoKernelTestBase {

  /**
   * Make sure the client factory returns a client object.
   */
  public function testCreate() {
    $factory = $this->container->get('brevo.brevo_client_factory');
    $this->assertInstanceOf(BrevoFactory::class, $factory);
    $this->assertInstanceOf(TransactionalEmailsApi::class, $factory->createTransactionalEmailsApiClient());
    $this->assertInstanceOf(AccountApi::class, $factory->createAccountApiClient());
  }

}
