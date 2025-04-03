<?php

namespace Drupal\brevo_mailer\Plugin\Mail;

/**
 * Queue the email for sending with Brevo.
 *
 * @Mail(
 *   id = "brevo_queue_mail",
 *   label = @Translation("Brevo mailer (queued)"),
 *   description = @Translation("Sends the message using Brevo with queue.")
 * )
 */
class BrevoQueueMail extends BrevoMail {

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Build and queue the message.
    $brevo_message = $this->buildMessage($message);
    return $this->queueMessage($brevo_message);
  }

}
