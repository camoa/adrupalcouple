<?php

namespace Drupal\brevo_mailer;

use Brevo\Client\Model\SendSmtpEmail;
use Drupal\brevo\BrevoFactory;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Mail handler to send out an email message array to the Brevo API.
 */
class BrevoMailerHandler implements BrevoMailerHandlerInterface {

  use StringTranslationTrait;

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
  protected $brevoMailerConfig;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Brevo Transactional emails API client.
   *
   * @var \brevo\Client\Api\TransactionalEmailsApi
   */
  protected $brevo;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new \Drupal\brevo_mailer\BrevoHandler object.
   *
   * @param \Drupal\brevo\BrevoFactory $brevo_factory
   *   Brevo factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(BrevoFactory $brevo_factory, ConfigFactoryInterface $config_factory, LoggerInterface $logger, MessengerInterface $messenger, EmailValidatorInterface $email_validator, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->brevoMailerConfig = $config_factory->get(BrevoMailerHandlerInterface::CONFIG_NAME);
    $this->logger = $logger;
    $this->brevoFactory = $brevo_factory;
    $this->brevo = $this->brevoFactory->createTransactionalEmailsApiClient();
    $this->messenger = $messenger;
    $this->emailValidator = $email_validator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(array $brevoMessage) {
    try {
      // Prepare the Brevo SDK method parameters.
      // @see https://developers.brevo.com/reference/sendtransacemail
      $params = new SendSmtpEmail($brevoMessage);
      $response = $this->brevo->sendTransacEmail($params);

      // Debug mode: log all messages.
      if ($this->brevoMailerConfig->get('debug_mode')) {
        $this->logger->notice('Successfully sent message. From: %from. Recipients: %to. %id.',
          [
            '%from' => $brevoMessage['sender']['email'],
            '%to' => $this->getRecipients($brevoMessage),
            '%id' => $response->getMessageId(),
          ]
        );
      }
      return $response;
    }
    catch (\Throwable $e) {
      // Catch all errors including ApiException and PHP deprecation errors
      // that may be thrown by the Brevo SDK on newer PHP versions.
      $this->logger->error('Exception occurred while trying to send test email. From: %from. Recipients: %to. Error code @code: @message',
        [
          '%from' => $brevoMessage['sender']['email'],
          '%to' => $this->getRecipients($brevoMessage),
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrupalMailerLibrary($showMessage = FALSE) {
    $libraryStatus = TRUE;
    $modules = $this->moduleHandler->getModuleList();
    if (empty($modules['mailsystem']) && empty($modules['symfony_mailer'])) {
      $libraryStatus = FALSE;
      if ($showMessage) {
        $this->messenger->addMessage('You must install one of the following drupal modules : "Mail System" or "Symfony Mailer".', 'warning');
      }
    }

    // Check for the Symfony Mailer dependencies.
    if (!empty($modules['symfony_mailer'])) {
      if (!class_exists('\Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport')) {
        $libraryStatus = FALSE;
        if ($showMessage) {
          $this->messenger->addMessage('You must install the "symfony/brevo-mailer" dependency (with composer).', 'warning');
        }
      }

      if (!class_exists('\Symfony\Component\HttpClient\HttpClient')) {
        $libraryStatus = FALSE;
        if ($showMessage) {
          $this->messenger->addMessage('You must install the "symfony/http-client" dependency (with composer).', 'warning');
        }
      }
    }

    return $libraryStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrupalMailerConfiguration($showMessage = FALSE) {
    $configurationStatus = TRUE;

    if ($this->moduleHandler->moduleExists('mailsystem')) {
      $sender = $this->configFactory->get('mailsystem.settings')->get('defaults.sender');
      if (!strstr($sender, 'brevo_')) {
        $configurationStatus = FALSE;
        if ($showMessage) {
          $this->messenger->addMessage($this->t('Brevo is not a default plugin (Mailsystem). You may update settings at @link.', [
            '@link' => Link::createFromRoute($this->t('here'), 'mailsystem.settings')->toString(),
          ]), 'warning');
        }
      }
    }

    if ($this->moduleHandler->moduleExists('symfony_mailer')) {
      // Display a warning if Brevo is not a default mailer for Symfony Mailer.
      $default_transport = $this->configFactory->get('symfony_mailer.settings')->get('default_transport');
      if (!strstr($default_transport, 'brevo')) {
        $default_non_overridden_transport = $this->configFactory->getEditable('symfony_mailer.settings')->get('default_transport');
        if ($default_non_overridden_transport === 'brevo') {
          $this->messenger->addMessage($this->t('Brevo is configured as the default transport (Symfony Mailer) but your current environment has overridden this with "@transport".', [
            '@transport' => $default_transport,
          ]), 'warning');
        }
        else {
          $this->messenger->addMessage($this->t('Brevo is not the default transport (Symfony Mailer). You may update settings at @link.', [
            '@link' => Link::createFromRoute($this->t('here'), 'entity.mailer_transport.collection')->toString(),
          ]), 'warning');
        }
      }
    }

    return $configurationStatus;
  }

  /**
   * Returns a list of recipients for error/debug log message.
   *
   * @param array $brevoMessage
   *   A message array, as described in
   *   https://developers.brevo.com/docs/send-a-transactional-email.
   *
   * @return string
   *   Recipients list in the following format:
   *   user@test.com, user1@test.com; cc: user2@test.com; bcc: user3@test.com.
   */
  public function getRecipients(array $brevoMessage) {
    $recipients = '';

    // Add all recipients (including 'cc' and 'bcc').
    foreach (['to', 'cc', 'bcc'] as $parameter) {
      if (!empty($brevoMessage[$parameter])) {
        foreach ($brevoMessage[$parameter] as $recipient) {
          $recipients .= "; {$parameter}: {$recipient['email']}";
        }
      }
    }

    return $recipients;
  }

}
