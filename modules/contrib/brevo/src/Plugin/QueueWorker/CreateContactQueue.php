<?php

namespace Drupal\brevo\Plugin\QueueWorker;

use Brevo\Client\Model\CreateContact;
use Drupal\brevo\BrevoFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for creating contacts in Brevo asynchronously.
 */
#[QueueWorker(
  id: 'brevo_create_contact',
  title: new TranslatableMarkup('Brevo: create contact'),
  cron: ['time' => 60]
)]
class CreateContactQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a CreateContactQueue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\brevo\BrevoFactory $brevoFactory
   *   The Brevo factory service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected BrevoFactory $brevoFactory,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('brevo.brevo_client_factory'),
      $container->get('logger.channel.brevo'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    try {
      $client = $this->brevoFactory->createContactsApiClient();
      $newContact = new CreateContact($data);
      $client->createContact($newContact);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create contact in Brevo: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
