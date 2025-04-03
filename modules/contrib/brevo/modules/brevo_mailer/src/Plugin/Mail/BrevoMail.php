<?php

namespace Drupal\brevo_mailer\Plugin\Mail;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RendererInterface;
use Html2Text\Html2Text;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default Brevo mail system plugin.
 *
 * @Mail(
 *   id = "brevo_mail",
 *   label = @Translation("Brevo mailer"),
 *   description = @Translation("Sends the message using Brevo.")
 * )
 */
class BrevoMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $brevoMailerConfig;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Brevo handler.
   *
   * @var \Drupal\brevo_mailer\BrevoMailerHandlerInterface
   */
  protected $brevoMailerHandler;

  /**
   * BrevoMail constructor.
   */
  public function __construct(ImmutableConfig $settings, LoggerInterface $logger, RendererInterface $renderer, QueueFactory $queueFactory, BrevoMailerHandlerInterface $brevoMailerHandler) {
    $this->brevoMailerConfig = $settings;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->queueFactory = $queueFactory;
    $this->brevoMailerHandler = $brevoMailerHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory')->get(BrevoMailerHandlerInterface::CONFIG_NAME),
      $container->get('logger.factory')->get('brevo_mailer'),
      $container->get('renderer'),
      $container->get('queue'),
      $container->get('brevo_mailer.mail_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    // If text format is specified in settings, run the message through it.
    $format = $this->brevoMailerConfig->get('format_filter');
    if (!empty($format)) {
      $message['body'] = check_markup($message['body'], $format, $message['langcode']);
    }

    // Skip theme formatting if the message does not support HTML.
    if (isset($message['params']['html']) && !$message['params']['html']) {
      return $message;
    }

    // Wrap body with theme function.
    if ($this->brevoMailerConfig->get('use_theme')) {
      $render = [
        '#theme' => isset($message['params']['theme']) ? $message['params']['theme'] : 'brevo',
        '#message' => $message,
      ];
      $message['body'] = $this->renderer->renderPlain($render);
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $brevo_message = $this->buildMessage($message);

    if ($this->brevoMailerConfig->get('use_queue')) {
      return $this->queueMessage($brevo_message);
    }
    return $this->brevoMailerHandler->sendMail($brevo_message);
  }

  /**
   * Queue a message for sending.
   *
   * @param array $message
   *   Brevo message array that was build and ready for sending.
   *
   * @return bool
   *   TRUE if the message was queued, otherwise FALSE.
   */
  public function queueMessage(array $message) {
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queueFactory->get('brevo_send_mail');

    $item = new \stdClass();
    $item->message = $message;
    $result = $queue->createItem($item);

    if ($result !== FALSE) {
      // Debug mode: log all messages.
      if ($this->brevoMailerConfig->get('debug_mode')) {
        $this->logger->notice('Successfully queued message. From: %from. Recipients: %to.', [
          '%from' => $message['from'] ?? 'N/A',
          '%to' => $this->brevoMailerHandler->getRecipients($message),
        ]);
      }
    }
    else {
      $this->logger->error('Unable to queue message. From: %from. Recipients: %to.', [
        '%from' => $message['from'] ?? 'N/A',
        '%to' => $this->brevoMailerHandler->getRecipients($message),
      ]);
    }

    return !empty($result);
  }

  /**
   * Builds the e-mail message in preparation to be sent to Brevo.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *   $message['params'] may contain additional parameters.
   *
   * @return array
   *   An email array formatted for Brevo delivery.
   *
   * @see https://developers.brevo.com/docs/send-a-transactional-email
   */
  protected function buildMessage(array $message) {
    // Add default values to make sure those array keys exist.
    $message += [
      'body' => [],
      'params' => [],
    ];

    // Build the Brevo message array.
    $brevo_message = [
      'subject' => $message['subject'],
      'htmlContent' => (string) $message['body'],
      'sender' => [
        'email' => $message['headers']['From'] ?? $message['from'],
      ],
      'to' => [
        ['email' => $message['to']],
      ],
    ];

    // Add replyTo if provided, fallback to sender if not set.
    if (!empty($message['reply-to'])) {
      $brevo_message['replyTo'] = [
        'email' => $message['reply-to'],
      ];
    }
    elseif (!empty($brevo_message['sender']['email'])) {
      $brevo_message['replyTo'] = $brevo_message['sender'];
    }

    // Add Cc / Bcc headers.
    foreach (['cc', 'bcc'] as $key) {
      $recipients = $message['headers'][ucfirst($key)] ?? $message['params'][$key] ?? [];
      if (!empty($recipients)) {
        $recipients = explode(',', $recipients);
        foreach ($recipients as $recipient) {
          $brevo_message[$key][] = ['email' => $recipient];
        }
      }
    }

    // Refine the recipient data, when recipient name is known.
    // Apply this for the sender, the main recipient and additional recipients.
    foreach (['sender', 'replyTo', 'to', 'cc', 'bcc'] as $key) {
      if (!empty($brevo_message[$key])) {
        if (in_array($key, ['sender', 'replyTo'])) {
          if (str_contains($brevo_message[$key]['email'], '<')) {
            preg_match_all('/(.*) <(.*)>/m', $brevo_message[$key]['email'], $matches, PREG_SET_ORDER);
            if (!empty($matches[0][1]) && !empty($matches[0][2])) {
              $brevo_message[$key] = [
                'name' => substr($matches[0][1], 0, 70),
                'email' => $matches[0][2],
              ];
            }
          }
        } else {
          foreach ($brevo_message[$key] as $i => $recipient) {
            if (str_contains($recipient['email'], '<')) {
              preg_match_all('/(.*) <(.*)>/m', $recipient['email'], $matches, PREG_SET_ORDER);
              if (!empty($matches[0][1]) && !empty($matches[0][2])) {
                $brevo_message[$key][$i] = [
                  'name' => substr($matches[0][1], 0, 70),
                  'email' => $matches[0][2],
                ];
              }
            }
          }
        }
      }
    }

    // Remove HTML version if the message does not support HTML.
    if (isset($message['params']['html']) && !$message['params']['html']) {
      unset($brevo_message['htmlContent']);
    }

    // Set text version of the message.
    if (isset($message['plain'])) {
      $brevo_message['textContent'] = $message['plain'];
    }
    else {
      $converter = new Html2Text($message['body'], ['width' => 0]);
      $brevo_message['textContent'] = $converter->getText();
    }

    // Include custom MIME headers (for example, 'X-My-Header').
    foreach ($message['headers'] as $key => $value) {
      if (!str_contains($key, 'X-')) {
        $brevo_message['headers'][$key] = $value;
      }
    }

    // For a full list of allowed parameters,
    // see: https://developers.brevo.com/docs/send-a-transactional-email#send-a-transactional-email-using-a-basic-html-content
    $allowed_params = ['templateId', 'params', 'tags', 'scheduledAt', 'batchId', 'messageVersions'];
    foreach ($message['params'] as $key => $value) {
      if (in_array($key, $allowed_params)) {
        $brevo_message[$key] = $value;
      }
    }

    // Brevo will accept the message but will not send it.
    // @see https://developers.brevo.com/docs/using-sandbox-mode-to-send-an-email
    if ($this->brevoMailerConfig->get('test_mode')) {
      $brevo_message['headers']['X-Sib-Sandbox'] = 'drop';
    }

    // Make sure the files provided in the attachments array exist.
    if (!empty($message['params']['attachments'])) {
      $attachments = [];
      foreach ($message['params']['attachments'] as $attachment) {
        if (!empty($attachment['filepath']) && file_exists($attachment['filepath'])) {
          $fileContent = file_get_contents($attachment['filepath']);
          $attachments[] = [
            'content' => base64_encode($fileContent),
            'name' => basename($attachment['filepath']),
          ];
        }
        elseif (!empty($attachment['filecontent']) && !empty($attachment['filename'])) {
          $attachments[] = [
            'content' => $attachment['filecontent'],
            'name' => $attachment['filename'],
          ];
        }
      }

      if (count($attachments) > 0) {
        $brevo_message['attachment'] = $attachments;
      }
    }

    return $brevo_message;
  }

}
