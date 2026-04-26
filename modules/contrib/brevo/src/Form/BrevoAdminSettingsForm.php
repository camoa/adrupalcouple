<?php

namespace Drupal\brevo\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\brevo\BrevoHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Brevo configuration form.
 */
class BrevoAdminSettingsForm extends ConfigFormBase {

  /**
   * Brevo handler.
   *
   * @var \Drupal\brevo\BrevoHandlerInterface
   */
  protected $brevoHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('config.factory'),
          $container->get('config.typed'),
          $container->get('brevo.handler'),
      );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, BrevoHandlerInterface $brevo_handler) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->brevoHandler = $brevo_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      BrevoHandlerInterface::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brevo_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $entered_api_key = $form_state->getValue('api_key');
    if (!empty($entered_api_key) && $this->brevoHandler->validateBrevoApiKey($entered_api_key) === FALSE) {
      $form_state->setErrorByName('api_key', $this->t("Couldn't connect to the Brevo API. Please check your API settings."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->brevoHandler->validateBrevoLibrary(TRUE);
    $config = $this->config(BrevoHandlerInterface::CONFIG_NAME);

    // Load not-editable configuration object to check actual api key value
    // including overrides.
    $not_editable_config = $this->configFactory()->get(BrevoHandlerInterface::CONFIG_NAME);

    $form['logo'] = [
      '#markup' => Markup::create('<div class="brevo-logo"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" viewBox="0 0 32 32">
					<circle cx="16" cy="16" r="16" fill="#0B996E"></circle>
  					<path fill="#fff" d="M21.002 14.54c.99-.97 1.453-2.089 1.453-3.45 0-2.814-2.07-4.69-5.19-4.69H9.6v20h6.18c4.698 0 8.22-2.874 8.22-6.686 0-2.089-1.081-3.964-2.998-5.174Zm-8.62-5.538h4.573c1.545 0 2.565.877 2.565 2.208 0 1.513-1.329 2.663-4.048 3.54-1.854.574-2.688 1.059-2.997 1.634l-.094.001V9.002Zm3.151 14.796h-3.152v-3.085c0-1.362 1.175-2.693 2.813-3.208 1.453-.484 2.657-.969 3.677-1.482 1.36.787 2.194 2.148 2.194 3.57 0 2.42-2.35 4.205-5.532 4.205Z"></path>
				</svg>
				<svg xmlns="http://www.w3.org/2000/svg" width="80" height="25" fill="currentColor" viewBox="0 0 90 31">
					<path fill="#0B996E" d="M73.825 19.012c0-4.037 2.55-6.877 6.175-6.877 3.626 0 6.216 2.838 6.216 6.877s-2.59 6.715-6.216 6.715c-3.626 0-6.175-2.799-6.175-6.715Zm-3.785 0c0 5.957 4.144 10.155 9.96 10.155 5.816 0 10-4.198 10-10.155 0-5.957-4.143-10.314-10-10.314s-9.96 4.278-9.96 10.314ZM50.717 8.937l7.81 19.989h3.665l7.81-19.989h-3.945L60.399 24.37h-.08L54.662 8.937h-3.945Zm-15.18 9.354c.239-3.678 2.67-6.156 5.977-6.156 2.867 0 5.02 1.84 5.338 4.598h-6.614c-2.35 0-3.626.28-4.58 1.56h-.12v-.002Zm-3.784.6c0 5.957 4.183 10.274 9.96 10.274 3.904 0 7.33-1.998 8.804-5.158l-3.187-1.6c-1.115 2.08-3.267 3.319-5.618 3.319-2.83 0-5.379-2.16-5.379-4.238 0-1.08.718-1.56 1.753-1.56h12.63v-1.079c0-5.997-3.825-10.155-9.323-10.155-5.497 0-9.641 4.279-9.641 10.195M20.916 28.924h3.586V16.653c0-2.639 1.632-4.518 3.905-4.518.956 0 1.951.32 2.43.758.36-.96.917-1.918 1.753-2.878-.957-.799-2.59-1.32-4.184-1.32-4.382 0-7.49 3.279-7.49 7.956v12.274-.001Zm-17.33-13.23V5.937h5.896c1.992 0 3.307 1.16 3.307 2.919 0 1.998-1.713 3.518-5.218 4.677-2.39.759-3.466 1.399-3.865 2.16h-.12Zm0 9.794v-4.077c0-1.799 1.514-3.558 3.626-4.238 1.873-.64 3.425-1.28 4.74-1.958 1.754 1.04 2.829 2.837 2.829 4.717 0 3.198-3.028 5.556-7.132 5.556H3.586ZM0 28.926h7.968c6.057 0 10.597-3.798 10.597-8.835 0-2.759-1.393-5.237-3.864-6.836 1.275-1.28 1.873-2.76 1.873-4.559 0-3.717-2.67-6.196-6.693-6.196H0v26.426Z"></path>
				</svg></div>'),
    ];

    $account = NULL;
    if (!empty($apiKey = $config->get('api_key')) || !empty($apiKey = $not_editable_config->get('api_key'))) {
      try {
        $account = $this->brevoHandler->getBrevoAccount($apiKey);
      }
      catch (\Throwable $e) {
        // Catch all errors including ApiException and PHP deprecation errors
        // that may be thrown by the Brevo SDK on newer PHP versions.
        $this->messenger()->addError($this->t('Can not fetch Brevo account : @error', ['@error' => $e->getMessage()]));
      }
    }

    if (!empty($account)) {
      // Show account information.
      $accountMarkup = '<p><strong>' . $this->t('You are currently logged in as :') . "</strong></p><p>" . $account->getFirstName() . ' ' . $account->getLastName() . ' - ' . $account->getEmail();
      if (!empty($companyName = $account->getCompanyName())) {
        $accountMarkup .= "<br/>" . $this->t('Company : @company', ['@company' => $companyName]);
      }

      if (!empty($plans = $account->getPlan())) {
        foreach ($plans as $plan) {
          $accountMarkup .= "<br/>" . $plan->getType() . ' - ' . $plan->getCredits() . ' ' . $this->t('credits');
        }
      }

      $accountMarkup .= '</p>';

      $form['account'] = [
        '#title' => $this->t('My account'),
        '#type' => 'fieldset',
      ];

      $form['account']['details'] = [
        '#type' => 'markup',
        '#markup' => $accountMarkup,
      ];

      $form['account']['logout'] = [
        '#type' => 'submit',
        '#value' => $this->t('Logout'),
        '#submit' => ['::logoutSubmitForm'],
      ];

    }
    else {
      // Show onboarding form.
      $form['onboarding']['step_1'] = [
        '#type' => 'details',
        '#title' => $this->t('Step 1: Create a Brevo Account'),
        '#open' => TRUE,
      ];

      $form['onboarding']['step_1']['description'] = [
        '#markup' => '<p>' . $this->t('By creating a free Brevo account, you will be able to send confirmation emails and:') . '</p><ul><li>' . $this->t('Collect your contacts and upload your lists') . '</li><li>' . $this->t('Use Brevo SMTP to send your transactional emails') . '</li><li>' . $this->t('Email marketing builders') . '</li><li>' . $this->t('Create and schedule your email marketing campaigns') . '</li><li><a href="https://www.brevo.com/features/?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link">' . $this->t("Try all of Brevo's features") . '</a></li></ul><a href="https://onboarding.brevo.com/account/register/?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link" class="button button--primary">' . $this->t('Create an account') . '</a>',
      ];

      $form['onboarding']['step_2'] = [
        '#type' => 'details',
        '#title' => $this->t('Step 2: Activate your account with your API key v3'),
        '#open' => TRUE,
      ];

      $form['onboarding']['step_2']['description'] = [
        '#markup' => '<p>' . $this->t('Once you have created a Brevo account, activate this plugin to send all of your transactional emails via Brevo SMTP. Brevo optimizes email delivery to ensure emails reach the inbox.') . '</p>' .
        '<p>' . $this->t('To activate your plugin, enter your API v3 Access key.') . '</p>' .
        '<p><a href="https://app.brevo.com/settings/keys/api?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link">' . $this->t('Get your API key from your account') . '</a></p>',
      ];

      $form['onboarding']['step_2']['api_key'] = [
        '#title' => $this->t('Brevo API Key'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $config->get('api_key'),
      ];
    }

    // Don't show other settings until we don't set API key.
    if (empty($not_editable_config->get('api_key'))) {
      return parent::buildForm($form, $form_state);
    }

    // If "API Key" is overridden in settings.php it won't be visible in form.
    // We have to make the field optional and allow to configure other settings.
    if (empty($config->get('api_key')) && !empty($not_editable_config->get('api_key'))) {
      $form['account']['api_key']['#required'] = FALSE;
    }

    $form['automation'] = [
      '#title' => $this->t('Automation'),
      '#type' => 'fieldset',
    ];

    if (!empty($account) && !empty($accountMarketingAutomation = $account->getMarketingAutomation())) {
      // Get the account automation identifier.
      $form['automation']['activate_marketing_automation'] = [
        '#title' => $this->t('Activate Marketing Automation through Brevo'),
        '#type' => 'checkbox',
        '#description' => $this->t('Check this box if you want to use @link  to track your website activity.', [
          '@link' => Link::fromTextAndUrl($this->t('Brevo Automation'), Url::fromUri('https://automation.brevo.com/parameters'))->toString(),
        ]) . "<br/>" . $this->t('When enabled, the Brevo JavaScript SDK will be added to all pages of your website.'),
        '#default_value' => $config->get('activate_marketing_automation'),
      ];

      $form['automation']['client_key'] = [
        '#title' => $this->t('Marketing Automation client key'),
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#value' => $accountMarketingAutomation->getKey(),
        '#description' => $this->t("This is your client key which is automatically fetched from Brevo's API."),
      ];

    }
    else {
      // The admin first have to enable the automations in Brevo.
      $form['automation']['activate_marketing_automation'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Please enable the automation in your @link dashboard before coming back here.', [
          '@link' => Link::fromTextAndUrl($this->t('Brevo Automation'), Url::fromUri('https://automation.brevo.com/parameters'))->toString(),
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_keys = ['api_key', 'activate_marketing_automation', 'client_key'];
    $brevo_config = $this->config(BrevoHandlerInterface::CONFIG_NAME);
    foreach ($config_keys as $config_key) {
      if ($form_state->hasValue($config_key)) {
        $brevo_config->set($config_key, $form_state->getValue($config_key));
      }
    }
    $brevo_config->save();

    $this->messenger()->addMessage($this->t('The configuration options have been saved.'));
  }

  /**
   * Custom submit handler for the logout button.
   */
  public function logoutSubmitForm(array &$form, FormStateInterface $form_state) {
    $brevo_config = $this->config(BrevoHandlerInterface::CONFIG_NAME);
    $brevo_config->clear('api_key');
    $brevo_config->clear('client_key');
    $brevo_config->save();

    $this->messenger()->addStatus($this->t('Your Brevo account was disconnected from your Drupal website.'));
    $form_state->setRedirect('<current>');
  }

}
