<?php

declare(strict_types=1);

namespace Drupal\brevo_mailer\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\symfony_mailer\Entity\MailerTransport;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for Brevo mailer configuration changes.
 */
final class BrevoMailerSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a BrevoMailerSubscriber object.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Config save event handler.
   */
  public function configSave(ConfigCrudEvent $event): void {
    // Make sure we only react on Brevo settings changes.
    if ($event->getConfig()->getName() !== 'brevo.settings') {
      return;
    }

    // Synchronize changes with the symfony mailer transport, if necessary.
    if ($this->moduleHandler->moduleExists('symfony_mailer')) {
      $brevo_transport = MailerTransport::load('brevo');
      if (empty($brevo_transport)) {
        // Create a new Brevo transport if it does not exist yet.
        $brevo_transport = MailerTransport::create([
          'id' => 'brevo',
          'label' => 'Brevo',
          'plugin' => 'dsn',
        ]);
      }

      // Update the DSN of the transport with new API key.
      $brevo_transport->set('configuration', ['dsn' => 'brevo+api://' . $event->getConfig()->get('api_key') . '@default']);
      $brevo_transport->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => ['configSave'],
    ];
  }

}
