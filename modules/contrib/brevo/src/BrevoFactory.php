<?php

namespace Drupal\brevo;

use Brevo\Client\Api\AccountApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Defines the brevo factory.
 */
class BrevoFactory {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $brevoConfig;

  /**
   * Http client service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $httpClient;

  /**
   * Constructs BrevoFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    $this->brevoConfig = $configFactory->get(BrevoHandlerInterface::CONFIG_NAME);
    $this->httpClient = $httpClient;
  }

  /**
   * Create Brevo Account API client.
   *
   * @return \Brevo\Client\Api\AccountApi
   *   Brevo PHP SDK Account API client.
   */
  public function createAccountApiClient(?string $api_key = null) {
    if (!$this->isBrevoLibraryInstalled()) {
        return NULL;
    }

    $api_key = $api_key ?? (string) $this->brevoConfig->get('api_key');
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
    return new AccountApi($this->httpClient, $config);
  }

  /**
   * Create Brevo Transactional emails API client.
   *
   * @return \Brevo\Client\Api\TransactionalEmailsApi
   *   Brevo PHP SDK Transactional emails API client.
   */
  public function createTransactionalEmailsApiClient(?string $api_key = null) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    $api_key = $api_key ?? (string) $this->brevoConfig->get('api_key');
    $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
    return new TransactionalEmailsApi($this->httpClient, $config);
  }

  /**
   * Check if Brevo library is installed.
   *
   * @return bool
   */
  protected function isBrevoLibraryInstalled() {
    return class_exists('\Brevo\Client\Configuration');
  }
}
