<?php

namespace Drupal\brevo\Plugin\WebformHandler;

use Brevo\Client\ApiException;
use Brevo\Client\Model\GetAttributesAttributes;
use Brevo\Client\Model\GetSmtpTemplateOverview;
use Brevo\Client\Model\SendSmtpEmail;
use Brevo\Client\Model\SendSmtpEmailBcc;
use Brevo\Client\Model\SendSmtpEmailCc;
use Brevo\Client\Model\SendSmtpEmailReplyTo;
use Brevo\Client\Model\SendSmtpEmailTo;
use Drupal\brevo\BrevoFactory;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends a transactional email using Brevo API.
 *
 * @WebformHandler(
 *   id = "brevo_transactional",
 *   label = @Translation("Brevo Transactional Email"),
 *   category = @Translation("External"),
 *   description = @Translation("Sends a transactional email using Brevo API, allowing use of a template."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class BrevoTransactionalHandler extends EmailWebformHandler {

  /**
   * Brevo factory.
   *
   * @var \Drupal\brevo\BrevoFactory
   */
  protected BrevoFactory $brevoFactory;

  /**
   * Cache of available templates.
   *
   * @var array|null
   */
  protected ?array $brevoTemplates = NULL;

  /**
   * Cache of available attributes.
   *
   * @var array|null
   */
  protected ?array $brevoAttributes = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->brevoFactory = $container->get('brevo.brevo_client_factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array_merge(parent::defaultConfiguration(), [
      'template_id' => '',
      'brevo_params' => '',
      'debug' => FALSE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): array|MarkupInterface {
    $settings = $this->getSettings();

    // Simplify the [webform_submission:values:.*] tokens.
    array_walk($settings, function (&$value) {
      if (is_string($value)) {
        $value = preg_replace('/\[webform:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_role:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_access:type:([^:]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_group:role:([^:]+)\]/', '[group:\1]', $value);
        $value = preg_replace('/\[webform_group:owner:mail\]/', '[group:owner]', $value);
        $value = preg_replace('/\[webform_submission:(?:node|source_entity|values):([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/\[webform_submission:([^]]+)\]/', '[\1]', $value);
        $value = preg_replace('/(:raw|:value)(:html)?\]/', ']', $value);
      }
    });

    return [
      '#theme' => 'webform_handler_' . $this->pluginId . '_summary',
      '#handler' => $this,
      '#settings' => $settings,
    ];
  }

  /**
   * Get Brevo attributes for template params.
   *
   * @return array
   *   An array of Brevo attributes with default values.
   */
  protected function getBrevoAttributes(): array {
    if ($this->brevoAttributes !== NULL) {
      return $this->brevoAttributes;
    }

    $this->brevoAttributes = [];
    $attributesClient = $this->brevoFactory->createAttributesApiClient();

    if ($attributesClient === NULL) {
      return $this->brevoAttributes;
    }

    try {
      $result = $attributesClient->getAttributes();
      $attributes = $result?->getAttributes() ?? [];

      $this->brevoAttributes = array_combine(
        array_map(fn(GetAttributesAttributes $attr) => $attr->getName(), $attributes),
        array_map(fn(GetAttributesAttributes $attr) => $attr->getType() === 'float' ? 0 : '', $attributes)
      );
    }
    catch (\Throwable $e) {
      $this->messenger()->addWarning($this->t('Exception when calling Brevo AttributesApi: @message.', [
        '@message' => $e->getMessage(),
      ]));
    }

    return $this->brevoAttributes;
  }

  /**
   * Get available Brevo email templates.
   *
   * @return array
   *   An array of template options keyed by template ID.
   */
  protected function getBrevoTemplates(): array {
    if ($this->brevoTemplates !== NULL) {
      return $this->brevoTemplates;
    }

    $this->brevoTemplates = [];
    $transactionalClient = $this->brevoFactory->createTransactionalEmailsApiClient();

    if ($transactionalClient === NULL) {
      return $this->brevoTemplates;
    }

    try {
      $result = $transactionalClient->getSmtpTemplates();

      if ($result?->getCount()) {
        $activeTemplates = array_filter(
          $result->getTemplates(),
          fn(GetSmtpTemplateOverview $template) => $template->getIsActive()
        );

        $this->brevoTemplates = array_combine(
          array_map(fn(GetSmtpTemplateOverview $t) => $t->getId(), $activeTemplates),
          array_map(fn(GetSmtpTemplateOverview $t) => sprintf('%s (ID: %d)', $t->getName(), $t->getId()), $activeTemplates)
        );
      }
    }
    catch (\Throwable $e) {
      $this->messenger()->addWarning($this->t('Exception when calling Brevo TransactionalEmailsApi: @message.', [
        '@message' => $e->getMessage(),
      ]));
    }

    return $this->brevoTemplates;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultConfigurationValues(): array {
    if (isset($this->defaultValues)) {
      return $this->defaultValues;
    }

    $this->defaultValues = array_merge(parent::getDefaultConfigurationValues(), [
      'template_id' => array_key_first($this->getBrevoTemplates()),
      'brevo_params' => Yaml::encode([
        'contact' => $this->getBrevoAttributes(),
        'data' => [],
      ]),
    ]);

    return $this->defaultValues;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Hide elements not needed for Brevo transactional emails.
    $form['from']['#access'] = FALSE;
    $form['elements']['#access'] = FALSE;
    $form['attachments']['#access'] = FALSE;
    $form['additional']['sender_mail']['#access'] = FALSE;
    $form['additional']['sender_name']['#access'] = FALSE;
    $form['additional']['theme_name']['#access'] = FALSE;
    $form['additional']['parameters']['#access'] = FALSE;

    // Template configuration.
    $form['template_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Template configuration'),
      '#open' => TRUE,
    ];

    $form['template_data']['template_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Template ID'),
      '#default_value' => $this->configuration['template_id'],
      '#options' => $this->getBrevoTemplates(),
      '#required' => TRUE,
    ];

    $form['template_data']['brevo_params'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Params'),
      '#description' => $this->t('Use keys as set in your template (eg: for {{params.TEST}}, set "TEST: your-value")'),
      '#default_value' => $this->configuration['brevo_params'] ?: Yaml::encode([
        'contact' => $this->getBrevoAttributes(),
        'data' => [],
      ]),
    ];
    $form['template_data']['token_tree_link'] = $this->buildTokenTreeElement();

    // Development settings.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, sent emails will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['debug'] = (bool) $this->configuration['debug'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(WebformSubmissionInterface $webform_submission): array {
    $message = parent::getMessage($webform_submission);

    // Decode params.
    $message['brevo_params'] = $message['brevo_params'] ? Yaml::decode($message['brevo_params']) : [];
    $message['brevo_params']['message'] = $message['body'] ?? '';

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message): bool {
    if (!$this->validateMessage($webform_submission, $message)) {
      return FALSE;
    }

    $transactionalClient = $this->brevoFactory->createTransactionalEmailsApiClient();

    if ($transactionalClient === NULL) {
      $this->loggerFactory->get('brevo_webform')->error('Brevo library is not installed.');
      return FALSE;
    }

    // Remove webform_submission and handler to prevent memory limit issues.
    if (drupal_valid_test_ua()) {
      unset($message['webform_submission'], $message['handler']);
    }

    // Prepare the Brevo SDK parameters.
    // @see https://developers.brevo.com/reference/sendtransacemail
    $brevoParams = new SendSmtpEmail();
    $brevoParams->setTo([
      new SendSmtpEmailTo(['email' => $message['to_mail']]),
    ]);

    if (!empty($message['subject'])) {
      $brevoParams->setSubject($message['subject']);
    }

    if (!empty($message['template_id'])) {
      $brevoParams->setTemplateId((int) $message['template_id']);
    }

    if (!empty($message['bcc_mail'])) {
      $brevoParams->setBcc([
        new SendSmtpEmailBcc(['email' => $message['bcc_mail']]),
      ]);
    }

    if (!empty($message['cc_mail'])) {
      $brevoParams->setCc([
        new SendSmtpEmailCc(['email' => $message['cc_mail']]),
      ]);
    }

    if (!empty($message['reply_to'])) {
      $brevoParams->setReplyTo(
        new SendSmtpEmailReplyTo(['email' => $message['reply_to']])
      );
    }

    if (!empty($message['brevo_params'])) {
      $brevoParams->setParams((object) $message['brevo_params']);
    }

    try {
      $response = $transactionalClient->sendTransacEmail($brevoParams);
    }
    catch (ApiException $e) {
      $this->handleSendError($webform_submission, $message, $e);
      return FALSE;
    }

    $this->logSuccess($webform_submission, $message);

    if ($this->configuration['debug']) {
      $this->displayDebugInfo($webform_submission, $message, $response->getMessageId());
    }

    $this->saveMessageIdToSubmission($webform_submission, $message, $response->getMessageId());

    return TRUE;
  }

  /**
   * Handle send error.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param array $message
   *   The message array.
   * @param \Brevo\Client\ApiException $e
   *   The API exception.
   */
  protected function handleSendError(WebformSubmissionInterface $webform_submission, array $message, ApiException $e): void {
    $recipients = $this->formatRecipients($message);
    $key = $this->getWebform()->id() . '_' . $this->getHandlerId();

    $t_args = [
      '%handler' => $key,
      '%recipients' => $recipients,
      '@code' => $e->getCode(),
      '@message' => $e->getMessage(),
    ];

    $this->loggerFactory->get('brevo_webform')->error(
      'Failed to send email. Handler: %handler Recipients: %recipients. Error @code: @message',
      $t_args
    );

    if ($this->configuration['debug']) {
      $this->messenger()->addError($this->t(
        'Failed to send email. Handler: %handler Recipients: %recipients. Error @code: @message',
        $t_args
      ));
    }

    // Save error to submission notes.
    $notes = $webform_submission->getNotes();
    $notes .= "\n" . Yaml::encode([
      $this->getHandlerId() => [
        'error' => [
          'code' => $e->getCode(),
          'message' => $e->getMessage(),
        ],
      ],
    ]);
    $webform_submission->setNotes($notes);
    $webform_submission->resave();
  }

  /**
   * Format recipients for logging.
   *
   * @param array $message
   *   The message array.
   *
   * @return string
   *   Formatted recipients string.
   */
  protected function formatRecipients(array $message): string {
    $recipients = '';
    foreach (['to', 'bcc', 'cc'] as $type) {
      $key = $type . '_mail';
      if (!empty($message[$key])) {
        if (is_array($message[$key])) {
          foreach ($message[$key] as $recipient) {
            $recipients .= "; {$type}: {$recipient}";
          }
        }
        else {
          $recipients .= "; {$type}: {$message[$key]}";
        }
      }
    }
    return ltrim($recipients, '; ');
  }

  /**
   * Log successful send.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param array $message
   *   The message array.
   */
  protected function logSuccess(WebformSubmissionInterface $webform_submission, array $message): void {
    $context = [
      '@from_name' => $message['from_name'],
      '@from_mail' => $message['from_mail'],
      '@to_mail' => $message['to_mail'],
      '@subject' => $message['subject'],
      'link' => $webform_submission->id() ? $webform_submission->toLink($this->t('View'))->toString() : NULL,
      'webform_submission' => $webform_submission,
      'handler_id' => $this->getHandlerId(),
      'operation' => 'sent email',
    ];

    $this->loggerFactory->get('brevo_webform')->notice(
      "'@subject' sent to '@to_mail' from '@from_name' [@from_mail].",
      $context
    );

    if ($webform_submission->getWebform()->hasSubmissionLog()) {
      $this->getLogger('webform_submission')->notice(
        "'@subject' sent to '@to_mail' from '@from_name' [@from_mail].",
        $context
      );
    }
  }

  /**
   * Display debug info.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param array $message
   *   The message array.
   * @param string $messageId
   *   The Brevo message ID.
   */
  protected function displayDebugInfo(WebformSubmissionInterface $webform_submission, array $message, string $messageId): void {
    $t_args = [
      '%from_name' => $message['from_name'],
      '%from_mail' => $message['from_mail'],
      '%to_mail' => $message['to_mail'],
      '%subject' => $message['subject'],
      '%message_id' => $messageId,
    ];
    $this->messenger()->addWarning($this->t(
      '%subject sent to %to_mail from %from_name [%from_mail] (%message_id).',
      $t_args
    ));

    $debug_message = $this->buildDebugMessage($webform_submission, $message);
    $this->messenger()->addWarning($this->themeManager->renderPlain($debug_message));
  }

  /**
   * Save message ID to submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param array $message
   *   The message array.
   * @param string $messageId
   *   The Brevo message ID.
   */
  protected function saveMessageIdToSubmission(WebformSubmissionInterface $webform_submission, array $message, string $messageId): void {
    $notes = $webform_submission->getNotes();
    $notes .= "\n" . Yaml::encode([
      $this->getHandlerId() => [
        'message_id' => $messageId,
      ],
    ]);
    $webform_submission->setNotes($notes);

    // Replace [webform:handler] tokens in submission data.
    $submission_data = $webform_submission->getData();
    $has_token = strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE;

    if ($has_token) {
      $token_data = [
        'webform_handler' => [
          $this->getHandlerId() => [
            $webform_submission->getState() => $message + ['message_id' => $messageId],
          ],
        ],
      ];
      $submission_data = $this->replaceTokens($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
    }

    $webform_submission->resave();
  }

  /**
   * Build debug message.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $message
   *   An email message.
   *
   * @return array
   *   Debug message render array.
   */
  protected function buildDebugMessage(WebformSubmissionInterface $webform_submission, array $message): array {
    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Email: @title', ['@title' => $this->label()]),
    ];

    $values = [
      'from_name' => $this->t('From name'),
      'from_mail' => $this->t('From mail'),
      'to_mail' => $this->t('To mail'),
      'cc_mail' => $this->t('Cc mail'),
      'bcc_mail' => $this->t('Bcc mail'),
      'reply_to' => $this->t('Reply-to'),
      'return_path' => $this->t('Return path'),
      '---' => '---',
      'subject' => $this->t('Subject'),
      'template_id' => $this->t('Template ID'),
    ];

    foreach ($values as $name => $title) {
      if ($title === '---') {
        $build[$name] = ['#markup' => '<hr />'];
      }
      elseif (!empty($message[$name])) {
        $build[$name] = [
          '#type' => 'item',
          '#title' => $title,
          '#markup' => $message[$name],
          '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
        ];
      }
    }

    $build['body'] = [
      '#type' => 'item',
      '#title' => $this->t('Body'),
      '#markup' => Markup::create('<pre>' . htmlentities($message['body']) . '</pre>'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
    ];

    return $build;
  }

}
