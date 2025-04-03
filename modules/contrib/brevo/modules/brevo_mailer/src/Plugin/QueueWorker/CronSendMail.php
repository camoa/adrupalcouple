<?php

namespace Drupal\brevo_mailer\Plugin\QueueWorker;

/**
 * Sending mails on CRON run.
 *
 * @QueueWorker(
 *   id = "brevo_send_mail",
 *   title = @Translation("Brevo Cron Worker"),
 *   cron = {"time" = 10}
 * )
 */
class CronSendMail extends SendMailBase {}
