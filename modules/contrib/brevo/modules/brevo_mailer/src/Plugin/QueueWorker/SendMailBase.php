<?php

namespace Drupal\brevo_mailer\Plugin\QueueWorker;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the SendMail Queue Workers.
 */
class SendMailBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Brevo mailer config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $brevoMailerConfig;

  /**
   * Brevo Logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Brevo mailer mail handler.
   *
   * @var \Drupal\brevo_mailer\BrevoMailerHandlerInterface
   */
  protected $brevoMailerHandler;

  /**
   * SendMailBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $settings, LoggerInterface $logger, BrevoMailerHandlerInterface $brevo_mailer_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->brevoMailerConfig = $settings;
    $this->logger = $logger;
    $this->brevoMailerHandler = $brevo_mailer_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')->get(BrevoMailerHandlerInterface::CONFIG_NAME),
      $container->get('logger.factory')->get('brevo_mailer'),
      $container->get('brevo_mailer.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $result = $this->brevoMailerHandler->sendMail($data->message);

    if ($this->brevoMailerConfig->get('debug_mode')) {
      $this->logger->notice('Successfully sent message on CRON from %from to %to.',
        [
          '%from' => $data->message['from'] ?? 'unknown',
          '%to' => $data->message['to'] ?? 'unknown',
        ]
      );
    }

    if (!$result) {
      throw new \Exception('Brevo: email did not pass through API.');
    }
  }

}
