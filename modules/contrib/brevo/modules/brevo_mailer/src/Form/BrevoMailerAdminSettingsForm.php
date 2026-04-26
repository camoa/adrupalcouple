<?php

namespace Drupal\brevo_mailer\Form;

use Drupal\brevo_mailer\BrevoMailerHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\filter\FilterPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Brevo Mailer configuration form.
 */
class BrevoMailerAdminSettingsForm extends ConfigFormBase {

  /**
   * Brevo Mailer handler.
   *
   * @var \Drupal\brevo_mailer\BrevoMailerHandlerInterface
   */
  protected $brevoMailerHandler;

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('brevo_mailer.mail_handler'),
      $container->get('plugin.manager.filter'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    BrevoMailerHandlerInterface $brevo_mailer_handler,
    FilterPluginManager $filter_manager,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->brevoMailerHandler = $brevo_mailer_handler;
    $this->filterManager = $filter_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      BrevoMailerHandlerInterface::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brevo_mailer_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->brevoMailerHandler->validateDrupalMailerLibrary(TRUE);
    $this->brevoMailerHandler->validateDrupalMailerConfiguration(TRUE);
    $config = $this->config(BrevoMailerHandlerInterface::CONFIG_NAME);

    $form['debug_mode'] = [
      '#title' => $this->t('Enable Debug Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('debug_mode'),
      '#description' => $this->t('Enable to log every email and queueing.'),
    ];

    $form['test_mode'] = [
      '#title' => $this->t('Enable Test Mode'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('test_mode'),
      '#description' => $this->t('Brevo will accept the message but will not send it. This is useful for testing purposes.'),
    ];

    $form['advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['advanced_settings']['format'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Format'),
    ];

    $options = [
      '' => $this->t('None'),
    ];
    $filter_formats = filter_formats();
    foreach ($filter_formats as $filter_format_id => $filter_format) {
      $options[$filter_format_id] = $filter_format->label();
    }

    // Add additional description text if there is a recommended filter plugin.
    // To be sure we are using the correct plugin name, use the definition.
    $recommendation = '';
    if ($this->filterManager->hasDefinition('filter_autop')) {
      $filter_title = $this->filterManager
        ->getDefinition('filter_autop')['title'] ?? '';
      $recommendation = $this->t('Recommended format filters: @filter.', [
        '@filter' => $filter_title,
      ]);
    }

    $form['advanced_settings']['format']['format_filter'] = [
      '#title' => $this->t('Format filter'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('format_filter'),
      '#description' => $this->t('@text_format to use to render the message. @recommendation', [
        '@text_format' => Link::fromTextAndUrl($this->t('Text format'), Url::fromRoute('filter.admin_overview'))->toString(),
        '@recommendation' => $recommendation,
      ]),
    ];

    $form['advanced_settings']['format']['use_theme'] = [
      '#title' => $this->t('Use theme'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('use_theme'),
      '#description' => $this->t('Enable to pass the message through a theme function. Default "brevo" or pass one with $message["params"]["theme"].'),
    ];

    if ($this->moduleHandler->moduleExists('mailsystem')) {
      $form['advanced_settings']['use_queue'] = [
        '#title' => $this->t('Enable Queue'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('use_queue'),
        '#description' => $this->t('Enable to queue emails and send them out during cron run. You can also enable queue for specific email keys by selecting Brevo mailer (queued) plugin in @link.', [
          '@link' => Link::fromTextAndUrl($this->t('mail system configuration'), Url::fromRoute('mailsystem.settings'))->toString(),
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_keys = ['debug_mode', 'test_mode', 'format_filter', 'use_queue', 'use_theme'];
    $brevo_config = $this->config(BrevoMailerHandlerInterface::CONFIG_NAME);
    foreach ($config_keys as $config_key) {
      if ($form_state->hasValue($config_key)) {
        $brevo_config->set($config_key, $form_state->getValue($config_key));
      }
    }
    $brevo_config->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

}
