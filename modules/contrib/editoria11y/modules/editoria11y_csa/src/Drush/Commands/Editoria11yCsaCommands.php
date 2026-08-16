<?php

namespace Drupal\editoria11y_csa\Drush\Commands;

use Drupal\Core\State\StateInterface;
use Drupal\editoria11y_csa\Exception\LicenseManagerException;
use Drupal\editoria11y_csa\LicenseManager;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush commands for Editoria11y CSA license management.
 */
class Editoria11yCsaCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs an Editoria11yCsaCommands object.
   */
  public function __construct(
    #[Autowire(service: 'state')]
    protected StateInterface $state,
    #[Autowire(service: 'editoria11y_csa.license_manager')]
    protected LicenseManager $licenseManager,
  ) {
    parent::__construct();
  }

  /**
   * Store a license key for later activation.
   *
   * @param string $licenseKey
   *   The Freemius license key to store.
   */
  #[CLI\Command(name: 'editoria11y-csa:set-key', aliases: ['ed11y-set-key'])]
  #[CLI\Argument(name: 'licenseKey', description: 'The Freemius license key to store.')]
  #[CLI\Usage(name: 'editoria11y-csa:set-key sk_abc123', description: 'Store the given license key for use with editoria11y-csa:activate.')]
  public function setKey(string $licenseKey): void {
    $licenseKey = trim($licenseKey);
    if (empty($licenseKey)) {
      $this->logger()->error('License key cannot be empty.');
      return;
    }

    try {
      $this->licenseManager->saveLicenseKey($licenseKey);
    }
    catch (LicenseManagerException $e) {
      $this->logger()->error('Failed to store key: {msg}', ['msg' => $e->getMessage()]);
      return;
    }
    $this->logger()->success(dt('License key stored. Use editoria11y-csa:activate to activate.'));
  }

  /**
   * Activate this site using the stored license key.
   */
  #[CLI\Command(name: 'editoria11y-csa:activate', aliases: ['ed11y-activate'])]
  #[CLI\Usage(name: 'editoria11y-csa:activate', description: 'Activate the site license via the Freemius API.')]
  public function activate(): void {
    try {
      $licenseKey = $this->licenseManager->getStoredLicenseKey();
    }
    catch (LicenseManagerException $e) {
      $this->logger()->error('No stored license key found. Run editoria11y-csa:set-key first.');
      return;
    }

    try {
      $this->licenseManager->activateLicense($licenseKey);
      $this->logger()->success(dt('License activated.'));
    }
    catch (LicenseManagerException $e) {
      $this->logger()->error('Activation failed: {msg}', ['msg' => $e->getMessage()]);
    }
  }

  /**
   * Deactivate this site's license.
   */
  #[CLI\Command(name: 'editoria11y-csa:deactivate', aliases: ['ed11y-deactivate'])]
  #[CLI\Usage(name: 'editoria11y-csa:deactivate', description: 'Deactivate the license and notify Freemius.')]
  public function deactivate(): void {
    try {
      $this->licenseManager->deactivateLicense();
      $this->logger()->success(dt('License deactivated.'));
    }
    catch (LicenseManagerException $e) {
      $this->logger()->error('Deactivation failed: {msg}', ['msg' => $e->getMessage()]);
    }
  }

  /**
   * Check the license status with the Freemius API.
   */
  #[CLI\Command(name: 'editoria11y-csa:check-renewal', aliases: ['ed11y-check-renewal'])]
  #[CLI\Usage(name: 'editoria11y-csa:check-renewal', description: 'Query the Freemius API for the current license status.')]
  public function checkStatus(): void {
    try {
      $result = $this->licenseManager->checkStatus();
    }
    catch (LicenseManagerException $e) {
      $this->logger()->error('License check failed: {msg}', [
        'msg' => $e->getMessage(),
      ]);
      return;
    }

    if ($result['status'] === 'active') {
      if ($result['expiration']) {
        $this->logger()->success(dt('License active. Expires: @date', ['@date' => $result['expiration']]));
      }
      else {
        $this->logger()->success(dt('License active. No expiration date.'));
      }
    }
    else {
      $this->logger()->warning('License expired or cancelled.');
    }
  }

  /**
   * Lock license management so it can only be changed via Drush.
   */
  #[CLI\Command(name: 'editoria11y-csa:lock', aliases: ['ed11y-lock'])]
  #[CLI\Usage(name: 'editoria11y-csa:lock', description: 'Prevent license changes from the admin UI.')]
  public function lock(): void {
    $this->state->set('editoria11y_csa.license_locked', TRUE);
    $this->logger()->success(dt('License management locked. Use editoria11y-csa:unlock to re-enable UI changes.'));
  }

  /**
   * Unlock license management so it can be changed from the admin UI.
   */
  #[CLI\Command(name: 'editoria11y-csa:unlock', aliases: ['ed11y-unlock'])]
  #[CLI\Usage(name: 'editoria11y-csa:unlock', description: 'Allow license changes from the admin UI again.')]
  public function unlock(): void {
    $this->state->delete('editoria11y_csa.license_locked');
    $this->logger()->success(dt('License management unlocked. UI changes are now allowed.'));
  }

}
