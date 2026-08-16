<?php

namespace Drupal\editoria11y_csa\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\editoria11y\CSAStatus;
use Drupal\editoria11y_csa\Exception\LicenseManagerException;
use Drupal\editoria11y_csa\LicenseManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to define all settings of the module.
 *
 * @phpstan-consistent-constructor
 */
class Editoria11yCSASettings extends ConfigFormBase {

  /**
   * The state service.
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    StateInterface $state,
    protected LicenseManager $licenseManager,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state'),
      $container->get('editoria11y_csa.license_manager'),
      $container->get('logger.channel.editoria11y_csa'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'editoria11y_csa_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'editoria11y_csa.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $csaConfig = $this->config('editoria11y_csa.settings');
    $form['#attached']['library'][] = 'editoria11y/editoria11y-settings';

    $licenseStore = $this->licenseManager->licenseStore();
    $hasValidLicense = (bool) $licenseStore->get('api_token');
    $hasStoredKey = !$hasValidLicense && $this->licenseManager->hasStoredLicenseKey();

    $csaActive = CSAStatus::current($this->state);
    $isDevEnvironment = $this->licenseManager->isDevEnvironment();
    $licenseLocked = (bool) $this->state->get('editoria11y_csa.license_locked');

    // If a subscription cancellation prompt is pending, render only that panel.
    if ($form_state->get('confirm_subscription_cancellation')) {
      $form['confirm'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">'
        . '<p><strong>' . $this->t('Are you deactivating this site or cancelling altogether?') . '</strong></p>'
        . '<p>' . $this->t('You can manage your subscriptions at any time in your Freemius account.') . '</p>'
        . '</div>',
      ];
      $form['subscription_action'] = [
        '#type' => 'radios',
        '#title' => $this->t('Choose an action'),
        '#options' => [
          'deactivate_only' => $this->t('<strong>Deactivate this site</strong>, but keep my subscription active and renewing for use on another site'),
          'cancel_and_deactivate' => $this->t('<strong>Cancel my subscription</strong> to the Editoria11y CSA altogether'),
        ],
        '#default_value' => 'deactivate_only',
      ];
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm deactivation'),
        '#submit' => ['::submitSubscriptionAction'],
      ];
      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Never mind; keep site active'),
        '#submit' => ['::cancelSubscriptionConfirmation'],
        '#limit_validation_errors' => [],
      ];
      return $form;
    }

    // If a dev-environment deactivation confirmation is pending, render only
    // the confirmation panel and skip the normal form.
    if ($form_state->get('confirm_dev_deactivation')) {
      $form['confirm'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">'
        . '<p><strong>' . $this->t('This site appears to be a development or testing environment with an active license.') . '</strong></p>'
        . '<p>' . $this->t('How would you like to proceed?') . '</p>'
        . '</div>',
      ];
      $form['dev_action'] = [
        '#type' => 'radios',
        '#title' => $this->t('Choose an action'),
        '#options' => [
          'cancel' => $this->t('<strong>Cancel deactivation</strong>; I will manage this from the production site'),
          'local' => $this->t('<strong>Disconnect this copy</strong>; leave the production license active'),
          'full' => $this->t('<strong>Deactivate all copies of this site</strong> (production, dev, and QA)'),
        ],
        '#default_value' => 'cancel',
      ];
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
        '#submit' => ['::submitDevAction'],
      ];
      return $form;
    }

    $form['license'] = [
      '#type' => 'container',
    ];

    $form['license']['status'] = [
      '#type' => 'container',
    ];

    $form['license']['manage'] = [
      '#type' => 'container',
      '#open' => $csaActive === CSAStatus::Off || $csaActive === CSAStatus::Trial,
    ];
    $form['license']['manage']['links'] = [
      '#type' => 'container',
      '#markup' => '<p>'
      . $this->t('Editoria11y promotes accessibility in a unique way. Its tools are highly effective at helping non-technical authors prepare content that can be enjoyed equally by disabled Web users. We consider this a public good, so Editoria11y will always be free to use.')
      . '</p><p>'
      . $this->t('Editoria11y is not, however, free to develop or support.')
      . '</p><p>'
      . $this->t('The "Community Supported Add-ons" (CSA) project fills the gap: project membership subscriptions fund the development of the Editoria11y library, its CMS plugins, and the CSA suite.')
      . '</p><ul><li>'
      . $this->t('<a href="@url"><strong>Join the CSA</strong></a> to support this project', ['@url' => 'https://editoria11y.com/license/'])
      . '</li><li>'
      . $this->t('Existing members: <a href="@url"><strong>Manage your subscription</strong></a> or <a href="@support_url"><strong>contact support</strong></a>', [
        '@url' => 'https://customers.freemius.com/login',
        '@support_url' => 'https://editoria11y.com/contacts',
      ])
      . '</li></ul><p></p>',
    ];

    if ($licenseLocked) {
      $form['license']['manage']['lock_warning'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">'
        . $this->t('License management on this site has been locked via Drush. Use <code>drush ed11y-unlock</code> to re-enable changes from this form.')
        . '</div>',
      ];
    }
    elseif ($isDevEnvironment) {
      $form['license']['manage']['dev_warning'] = [
        '#type' => 'markup',
        '#markup' => '<div class="messages messages--warning">' .
        // phpcs:ignore-next-line
        '<h3>' . $this->t('Warning: this site matches a dev or QA URL pattern') . '</h3>'
        . '<p>'
        . $this->t('Shared activations should be managed in the production copy.')
        . '<br>'
        . $this->t('Use <code>drush ed11y-lock</code> to prevent changing shared activations.') . '</p>'
        . '<ul><li><strong>' . $this->t('Deactivating a shared key deactivates all copies of a site, including production.')
        . '</strong></li><li>'
        . $this->t('Separate license activations from a development environment consume an additional key.')
        . '</li></ul>'
        . '</div>',
      ];
    }

    $csaOptions = [
      CSAStatus::Off->value => $this->t('Off'),
    ];
    if ($this->licenseManager->isTrialActive()) {
      $daysLeft = $this->licenseManager->getTrialDaysRemaining();
      $csaOptions[CSAStatus::Trial->value] = $this->t('Trial (@days days remaining)', ['@days' => $daysLeft]);
    }
    elseif ($csaActive === CSAStatus::Trial) {
      $csaOptions[CSAStatus::Trial->value] = $this->t('Trial (expired)');
    }
    if ($hasValidLicense) {
      if ($csaActive === CSAStatus::LicenseExpired) {
        $csaOptions[CSAStatus::LicenseExpired->value] = $this->t('License expired');
      }
      else {
        $csaOptions[CSAStatus::Licensed->value] = $this->t('Active membership');
      }
    }
    else {
      if ($hasStoredKey) {
        $csaOptions[CSAStatus::ActivateStored->value] = $this->t('Activate using stored license key');
      }
      $csaOptions[CSAStatus::Activate->value] = $hasStoredKey
        ? $this->t('Activate with a different license key')
        : $this->t('Activate (enter license key below)');
    }

    if ($hasValidLicense) {
      $expiration = $licenseStore->get('expiration');
      if (!empty($expiration)) {
        $expiration = date('Y-m-d', strtotime($expiration));
      }
      $isTrial = (bool) $licenseStore->get('trial');
      $trialEndsAt = $licenseStore->get('trial_ends_at');
      if (!empty($trialEndsAt)) {
        $trialEndsAt = date('Y-m-d', strtotime($trialEndsAt));
      }

      if ($csaActive === CSAStatus::LicenseExpired) {
        $licenseDescription = $expiration
          ? $this->t('<strong>License expired.</strong> Expired on: @date', ['@date' => $expiration])
          : $this->t('<strong>License has expired.</strong>');
      }
      elseif ($isTrial && $trialEndsAt) {
        $licenseDescription = $this->t('<strong>Trial license active.</strong> Trial ends: @date', ['@date' => $trialEndsAt]);
      }
      elseif ($expiration) {
        $licenseDescription = $this->t('<strong>License active.</strong> Expires: @date', ['@date' => $expiration]);
      }
      else {
        $licenseDescription = $this->t('<strong>Lifetime license active.</strong>');
      }

      $form['license']['status']['license_status'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $licenseDescription . '</p>',
      ];
      if ($csaActive === CSAStatus::LicenseExpired) {
        $form['license']['status']['check_renewal'] = [
          '#type' => 'submit',
          '#value' => $this->t('Check for license renewal'),
          '#submit' => ['::checkRenewal'],
          '#limit_validation_errors' => [],
        ];
      }
      if (!$licenseLocked) {
        $form['license']['manage']['deactivate'] = [
          '#type' => 'submit',
          '#value' => $this->t('Deactivate license'),
          '#submit' => ['::deactivateLicense'],
          '#limit_validation_errors' => [],
        ];
      }
    }
    else {

      $licenseDescription = $hasStoredKey
        ? '<hr><h2>' . $this->t('A license key is stored but not active') . '</h2>'
        : '<hr><h2>' . $this->t('Add a license key to enable CSA features') . '</h2>';
      $form['license']['manage']['license_status'] = [
        '#type' => 'markup',
        '#markup' => $licenseDescription,
      ];

      // @todo on deactivation, show warning that this did not end the subscription and link to Freemius account management.
      // Only renders when there is no active license.
      $form['license']['manage']['activation_status'] = [
        '#type' => 'radios',
        '#options' => $csaOptions,
        '#title' => $this->t('Enable CSA features'),
        '#default_value' => $csaActive->value,
        '#disabled' => $licenseLocked,
      ];
    }

    if ($csaActive !== CSAStatus::Licensed) {
      $form['license']['manage']['license_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CSA license key'),
          // @todo link
        '#description' => ''
        . '<p>' . $this->t('<a href="@url">Join the CSA</a> to enable CSA features. Visit the Freemius dashboard to <a href="@freemius">manage your subscription</a> or recover a lost key.', [
          '@url' => 'https://editoria11y.com/license/',
          '@freemius' => 'https://freemius.com/dashboard/',
        ]) . '</p>',
        '#default_value' => '',
        '#states' => [
          'enabled' => [
            ':input[name="activation_status"]' => ['value' => 'activate'],
          ],
          'required' => [
            ':input[name="activation_status"]' => ['value' => 'activate'],
          ],
        ],
        '#maxlength' => 255,
        '#disabled' => $licenseLocked,
      ];
    }

    $form['license']['manage']['dev_domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Local, Dev and QA site URL patterns'),
      '#description' => $this->t('One pattern per line, up to 5 patterns. Wildcards are supported (e.g. <code>*.test.example.com</code>).<br>Local, dev and QA sites can share an activation with production site, but only production sites check for renewals.'),
      '#default_value' => $csaConfig->get('dev_domains'),
      '#rows' => 5,
      '#disabled' => $licenseLocked,
    ];
    $patternsList = '<ul>' . implode('', array_map(
      fn($p) => '<li><code>' . htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</code></li>',
      LicenseManager::DEV_PATTERNS
    )) . '</ul><p>' . $this->t('Additionally, any hostname consisting only of digits and dots (raw IPv4 address) is treated as a development environment.') . '</p>';
    $form['license']['manage']['dev_patterns_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Built-in development URL patterns'),
      '#open' => FALSE,
    ];
    $form['license']['manage']['dev_patterns_info']['list'] = [
      '#markup' => $patternsList,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $devDomains = $form_state->getValue('dev_domains') ?? '';
    $lines = array_filter(array_map('trim', explode("\n", $devDomains)));
    if (count($lines) > 5) {
      $form_state->setErrorByName('dev_domains', $this->t('Enter no more than 5 custom domain patterns.'));
    }

    $csaActiveValue = CSAStatus::tryFrom(
      $form_state->getValue('activation_status') ?? ''
    );
    if ($csaActiveValue === CSAStatus::ActivateStored) {
      try {
        $licenseKey = $this->licenseManager->getStoredLicenseKey();
      }
      catch (LicenseManagerException $e) {
        $form_state->setErrorByName('activation_status', $this->t('Saved license key not found. Please activate with a new key.'));
        parent::validateForm($form, $form_state);
        return;
      }
      $form_state->set('pending_license_key', $licenseKey);
    }
    elseif ($csaActiveValue === CSAStatus::Activate) {
      $licenseKey = trim((string) ($form_state->getValue('license_key') ?? ''));
      if (empty($licenseKey)) {
        $form_state->setErrorByName('license_key', $this->t('Enter a license key.'));
        parent::validateForm($form, $form_state);
        return;
      }
      $form_state->set('pending_license_key', $licenseKey);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // On a dev environment, changing the radio away from a licensed state
    // requires a confirmation step about whether to notify Freemius.
    $savedStatus = CSAStatus::current($this->state);
    // When a license is active the radio is not rendered; treat a missing value
    // as "keep the current state" to avoid falsely triggering deactivation.
    $newStatusValue = $form_state->getValue('activation_status') ?? $savedStatus->value;
    $newStatus = CSAStatus::tryFrom($newStatusValue) ?? $savedStatus;
    if (
      $this->licenseManager->isDevEnvironment()
      && $savedStatus->isLicensed()
      && !$newStatus->isLicensed()
    ) {
      $form_state->set('confirm_dev_deactivation', TRUE);
      $form_state->setRebuild(TRUE);
      return;
    }

    // Only persist real activation states.
    if (in_array($newStatus, [CSAStatus::Off, CSAStatus::Trial], TRUE)) {
      $this->state->set(CSAStatus::STATE_KEY, $newStatus->value);
    }

    // Test configuration, assertiveness, and contrast_ignore now live on the
    // main settings form; this form only persists license/environment fields.
    $this->config('editoria11y_csa.settings')
      ->set('dev_domains', $form_state->getValue('dev_domains'))
      ->save();

    $pendingKey = $form_state->get('pending_license_key');
    if ($pendingKey) {
      try {
        $this->licenseManager->activateLicense($pendingKey);
        $this->messenger()->addStatus($this->t('License activated.'));
      }
      catch (LicenseManagerException $e) {
        $this->messenger()->addError($this->t('Activation failed: @msg', ['@msg' => $e->getMessage()]));
      }
    }

    Cache::invalidateTags([
      'config:editoria11y.settings',
    ]);
    // Increment config version to bust browser cache for the config API.
    $v = $this->state->get('editoria11y.config_version', 0);
    $this->state->set('editoria11y.config_version', $v + 1);
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the "Check for license renewal" button.
   */
  public function checkRenewal(array &$form, FormStateInterface $form_state): void {
    try {
      $result = $this->licenseManager->checkStatus();
      match ($result['status']) {
        'active' => $this->messenger()->addStatus($this->t('License renewed and active.')),
        'expired' => $this->messenger()->addWarning($this->t('License is still expired.')),
        default => $this->messenger()->addWarning($this->t('Unexpected license status: @status', ['@status' => $result['status']])),
      };
    }
    catch (LicenseManagerException $e) {
      $this->messenger()->addError($this->t('Could not reach licensing server: @msg', ['@msg' => $e->getMessage()]));
    }
  }

  /**
   * Submit handler for the deactivate license button.
   */
  public function deactivateLicense(array &$form, FormStateInterface $form_state): void {
    if ($this->licenseManager->isDevEnvironment()) {
      $form_state->set('confirm_dev_deactivation', TRUE);
      $form_state->setRebuild(TRUE);
      return;
    }
    $licenseId = $this->shouldOfferSubscriptionCancellation();
    if ($licenseId !== NULL) {
      $form_state->set('confirm_subscription_cancellation', $licenseId);
      $form_state->setRebuild(TRUE);
      return;
    }
    $this->performDeactivation(TRUE);
  }

  /**
   * Submit handler for the dev-environment deactivation confirmation.
   */
  public function submitDevAction(array &$form, FormStateInterface $form_state): void {
    $action = $form_state->getValue('dev_action');
    switch ($action) {
      case 'local':
        $this->performDeactivation(FALSE);
        break;

      case 'full':
        $licenseId = $this->shouldOfferSubscriptionCancellation();
        if ($licenseId !== NULL) {
          $form_state->set('confirm_dev_deactivation', NULL);
          $form_state->set('confirm_subscription_cancellation', $licenseId);
          $form_state->set('confirm_subscription_cancellation_from_dev', TRUE);
          $form_state->setRebuild(TRUE);
          return;
        }
        $this->performDeactivation(TRUE);
        break;

      default:
        // 'cancel': return to the main form.
        $form_state->set('confirm_dev_deactivation', NULL);
        $form_state->setRebuild(TRUE);
        break;
    }
  }

  /**
   * Submit handler for the subscription cancellation confirmation.
   */
  public function submitSubscriptionAction(array &$form, FormStateInterface $form_state): void {
    $action = $form_state->getValue('subscription_action');
    if ($action === 'cancel_and_deactivate') {
      $licenseId = $form_state->get('confirm_subscription_cancellation');
      $this->licenseManager->cancelSubscription((int) $licenseId);
    }
    $this->performDeactivation(TRUE);
  }

  /**
   * Submit handler: go back from the subscription cancellation panel.
   *
   * Returns to the dev confirmation panel if the user arrived from that flow,
   * otherwise returns to the main settings form.
   */
  public function cancelSubscriptionConfirmation(array &$form, FormStateInterface $form_state): void {
    $fromDev = $form_state->get('confirm_subscription_cancellation_from_dev');
    $form_state->set('confirm_subscription_cancellation', NULL);
    $form_state->set('confirm_subscription_cancellation_from_dev', NULL);
    if ($fromDev) {
      $form_state->set('confirm_dev_deactivation', TRUE);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Checks whether we should offer the user a subscription cancellation prompt.
   *
   * Returns the stored license ID if a subscription cancellation prompt should
   * be offered, or NULL if the step should be skipped.
   *
   * The prompt is only relevant when the license is currently active and we
   * have a license ID on record to pass to the Freemius cancellation API.
   */
  private function shouldOfferSubscriptionCancellation(): ?int {
    if (CSAStatus::current($this->state) !== CSAStatus::Licensed) {
      return NULL;
    }
    $licenseId = $this->licenseManager->licenseStore()->get('license_id');
    return $licenseId ? (int) $licenseId : NULL;
  }

  /**
   * Removes the local license credentials, optionally notifying Freemius first.
   *
   * @param bool $notifyFreemius
   *   TRUE to send a deactivation request to the Freemius API before clearing
   *   local credentials; FALSE to disconnect locally only.
   */
  private function performDeactivation(bool $notifyFreemius): void {
    try {
      $this->licenseManager->deactivateLicense($notifyFreemius);
      $this->messenger()->addStatus($this->t('License deactivated.'));
    }
    catch (LicenseManagerException $e) {
      $this->messenger()->addError($this->t('Deactivation failed: @msg', ['@msg' => $e->getMessage()]));
    }
  }

}
