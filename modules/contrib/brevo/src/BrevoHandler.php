<?php

namespace Drupal\brevo;

use Brevo\Client\ApiException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Psr\Log\LoggerInterface;

/**
 * Mail handler to send out an email message array to the Brevo API.
 */
class BrevoHandler implements BrevoHandlerInterface {

  /**
   * Brevo factory.
   *
   * @var \Drupal\brevo\BrevoFactory
   */
  protected $brevoFactory;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $brevoConfig;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Brevo Transactional emails API client.
   *
   * @var \Brevo\Client\Api\TransactionalEmailsApi
   */
  protected $brevo;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new \Drupal\brevo\BrevoHandler object.
   *
   * @param \Drupal\brevo\BrevoFactory $brevo_factory
   *   Brevo factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(BrevoFactory $brevo_factory, ConfigFactoryInterface $config_factory, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->brevoConfig = $config_factory->get(BrevoHandlerInterface::CONFIG_NAME);
    $this->logger = $logger;
    $this->brevoFactory = $brevo_factory;
    $this->brevo =  $this->brevoFactory->createTransactionalEmailsApiClient();
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function moduleStatus($showMessage = FALSE) {
    return $this->validateBrevoLibrary($showMessage)
      && $this->validateBrevoApiSettings($showMessage);
  }

  /**
   * {@inheritdoc}
   */
  public function validateBrevoApiKey($key) {
    if (!$this->validateBrevoLibrary()) {
      return FALSE;
    }

    try {
      $brevo = $this->brevoFactory->createAccountApiClient($key);
      $brevo->getAccount();
    }
    catch (ApiException $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBrevoAccount($key) {
    if (!$this->validateBrevoLibrary()) {
      return null;
    }

    $brevo = $this->brevoFactory->createAccountApiClient($key);
    return $brevo->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function validateBrevoApiSettings($showMessage = FALSE) {
    $apiKey = $this->brevoConfig->get('api_key');
    if (empty($apiKey)) {
      if ($showMessage) {
        $this->messenger->addMessage("Please check your API settings. API key shouldn't be empty.", 'warning');
      }
      return FALSE;
    }

    if (!$this->validateBrevoApiKey($apiKey)) {
      if ($showMessage) {
        $this->messenger->addMessage("Couldn't connect to the Brevo API. Please check your API settings.", 'warning');
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateBrevoLibrary($showMessage = FALSE) {
    $libraryStatus = class_exists('\Brevo\Client\Api\TransactionalEmailsApi');
    if ($showMessage === FALSE) {
      return $libraryStatus;
    }

    if ($libraryStatus === FALSE) {
      $this->messenger->addMessage('The Brevo library has not been installed correctly.', 'warning');
    }
    return $libraryStatus;
  }

}
