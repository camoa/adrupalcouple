<?php

namespace Drupal\brevo_mailer\Form;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides test email form with common email parameters.
 */
class BrevoMailerTestEmailForm extends FormBase {

  /**
   * Drupal\brevo_mailer\BrevoMailerHandlerInterface definition.
   *
   * @var \Drupal\brevo_mailer\BrevoMailerHandlerInterface
   */
  protected $brevoMailerHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * Mail Manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * BrevoTestEmailForm constructor.
   */
  public function __construct(BrevoMailerHandlerInterface $brevo_mailer_handler, AccountProxyInterface $user, MailManagerInterface $mail_manager, FileSystemInterface $file_system, ModuleHandlerInterface $module_handler) {
    $this->brevoMailerHandler = $brevo_mailer_handler;
    $this->user = $user;
    $this->mailManager = $mail_manager;
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('brevo_mailer.mail_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.mail'),
      $container->get('file_system'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brevo_test_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->brevoMailerHandler->validateDrupalMailerLibrary(TRUE);
    $this->brevoMailerHandler->validateDrupalMailerConfiguration(TRUE);

    // We can test all mail systems with this form.
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#required' => TRUE,
      '#description' => $this->t('Email will be sent to this address. You can use commas to separate multiple recipients.'),
      '#default_value' => $this->user->getEmail(),
    ];

    $form['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => $this->t('Howdy!

If this email is displayed correctly and delivered sound and safe, congrats! You have successfully configured Brevo.'),
    ];

    $form['include_attachment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include attachment'),
      '#description' => $this->t('If checked, an image will be included as an attachment with the test email.'),
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional parameters'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('You may test more parameters to make sure they are working.'),
    ];
    $form['extra']['reply_to'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply-To'),
    ];
    $form['extra']['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];
    $form['extra']['bcc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BCC'),
      '#description' => $this->t('You can use commas to separate multiple recipients.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('brevo.admin_settings_form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('to');

    $params = [
      'subject' => $this->t('Brevo works!'),
      'body' => [$form_state->getValue('body')],
    ];

    if (!empty($form_state->getValue('include_attachment'))) {
      $params['attachments'][] = ['filepath' => $this->fileSystem->realpath('core/misc/druplicon.png')];
    }

    // Add CC / BCC values if they are set.
    if (!empty($cc = $form_state->getValue('cc'))) {
      $params['cc'] = $cc;
    }
    if (!empty($bcc = $form_state->getValue('bcc'))) {
      $params['bcc'] = $bcc;
    }

    $result = $this->mailManager->mail('brevo_mailer', 'test_form_email', $to, $this->user->getPreferredLangcode(), $params, $form_state->getValue('reply_to'), TRUE);
    if (!empty($result['result'])) {
      $this->messenger()->addMessage($this->t('Successfully sent message to %to.', ['%to' => $to]));
    }
    else {
      if ($this->moduleHandler->moduleExists('dblog')) {
        $this->messenger()->addMessage($this->t('Something went wrong. Please check @logs for details.', [
          '@logs' => Link::createFromRoute($this->t('logs'), 'dblog.overview')
            ->toString(),
        ]), 'error');
      }
      else {
        $this->messenger()->addMessage($this->t('Something went wrong. Please check logs for details.'), 'error');
      }
    }
  }

}
