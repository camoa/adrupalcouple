<?php

namespace Drupal\editoria11y_csa;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\editoria11y\CSAStatus;
use Drupal\editoria11y_csa\Exception\LicenseManagerException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages Freemius license validation, activation, and deactivation.
 */
class LicenseManager {

  const STATE_LAST_CHECK = 'editoria11y_csa.freemius_last_check';
  const LICENSE_COLLECTION = 'editoria11y_csa.license';
  const FREEMIUS_PRODUCT_ID = '25210';
  const INTERVAL_ACTIVE = 604800;
  const INTERVAL_EXPIRED = 86400;
  const TRIAL_DURATION = 2592000;
  const KEY_FALLBACK_ID = 'editoria11y_license';

  /**
   * Hostnames and glob patterns treated as development environments.
   */
  public const DEV_PATTERNS = [

    // -------------------------------------------------------------------------
    // Loopback / localhost
    // -------------------------------------------------------------------------
    'localhost',
    '127.0.0.1',
    '::1',

    // -------------------------------------------------------------------------
    // Generic local TLDs (RFC 2606 / common conventions)
    // -------------------------------------------------------------------------
    '*.local',
    '*.test',
    '*.localhost',
    // RFC 2606 reserved.
    '*.example',
    // RFC 2606 reserved.
    '*.invalid',

    // -------------------------------------------------------------------------
    // Generic subdomain patterns (host-agnostic)
    // -------------------------------------------------------------------------
    'local.*',
    'dev.*',
    'test.*',
    'stage.*',
    'staging.*',
    'qa.*',
    // User Acceptance Testing.
    'uat.*',
    'preprod.*',
    'preview.*',
    'sandbox.*',
    'demo.*',
    'beta.*',

    // -------------------------------------------------------------------------
    // Generic URL slug patterns
    // -------------------------------------------------------------------------
    '*-dev.*',
    'dev-*',
    '*-test.*',
    '*-stage.*',
    '*-staging.*',
    '*-qa.*',
    '*-uat.*',
    '*-preview.*',
    '*-sandbox.*',
    '*-preprod.*',

    // -------------------------------------------------------------------------
    // Local PHP / Drupal dev tools
    // -------------------------------------------------------------------------
    // DDEV (recommended local tool in Drupal docs)
    '*.ddev.site',
    // Lando.
    '*.lndo.site',
    // localtest.me — resolves to 127.0.0.1.
    '*.localtest.me',
    // Drupal.org local dev convention.
    '*.local.drupal.org',

    // -------------------------------------------------------------------------
    // Acquia
    // -------------------------------------------------------------------------
    // Acquia Cloud Platform default env domains
    '*.acquia-sites.com',
    '*dev.acquia-sites.com',
    '*stg.acquia-sites.com',
    // Older / internal Acquia environments.
    '*.acquia.com',
    '*.devcloud.hosting.acquia.com',

    // -------------------------------------------------------------------------
    // Pantheon
    // -------------------------------------------------------------------------
    '*.pantheonsite.io',
    'dev-*.pantheonsite.io',
    'test-*.pantheonsite.io',

    // -------------------------------------------------------------------------
    // Platform.sh / Upsun
    // -------------------------------------------------------------------------
    '*.platform.sh',
    '*.upsun.com',

    // -------------------------------------------------------------------------
    // Lagoon / amazee.io
    // -------------------------------------------------------------------------
    '*.lagoon.amazee.io',
    '*.lagoon.site',

    // -------------------------------------------------------------------------
    // Tugboat QA
    // -------------------------------------------------------------------------
    '*.tugboatqa.com',

    // -------------------------------------------------------------------------
    // WP Engine / Flywheel
    // -------------------------------------------------------------------------
    '*.wpengine.com',
    '*.wpenginepowered.com',
    '*.flywheelsites.com',
    '*.flywheelstaging.com',

    // -------------------------------------------------------------------------
    // Kinsta
    // -------------------------------------------------------------------------
    'staging-*.kinsta.com',
    'staging-*.kinsta.cloud',

    // -------------------------------------------------------------------------
    // Cloudways
    // -------------------------------------------------------------------------
    '*.cloudwaysapps.com',

    // -------------------------------------------------------------------------
    // SiteGround
    // -------------------------------------------------------------------------
    'staging*.siteground.com',
    '*.sitegroundstaging.com',

    // -------------------------------------------------------------------------
    // GoDaddy / MediaTemple / related
    // -------------------------------------------------------------------------
    '*.myftpupload.com',
    '*.godaddysites.com',

    // -------------------------------------------------------------------------
    // 10Web
    // -------------------------------------------------------------------------
    '*-dev.10web.site',
    '*-dev.10web.cloud',

    // -------------------------------------------------------------------------
    // Pressable
    // -------------------------------------------------------------------------
    '*.mystagingwebsite.com',

    // -------------------------------------------------------------------------
    // WPMU DEV / Incsub
    // -------------------------------------------------------------------------
    '*.tempurl.host',
    '*.wpmudev.host',

    // -------------------------------------------------------------------------
    // Vendasta
    // -------------------------------------------------------------------------
    '*.websitepro-staging.com',
    '*.websitepro.hosting',

    // -------------------------------------------------------------------------
    // InstaWP
    // -------------------------------------------------------------------------
    '*.instawp.co',
    '*.instawp.link',
    '*.instawp.xyz',

    // -------------------------------------------------------------------------
    // WPSandbox
    // -------------------------------------------------------------------------
    '*.wpsandbox.pro',

    // -------------------------------------------------------------------------
    // Tunneling tools (expose localhost to internet during dev)
    // -------------------------------------------------------------------------
    // ngrok (legacy free tier)
    '*.ngrok.io',
    // Ngrok (current free tier)
    '*.ngrok-free.app',
    // Ngrok (paid / named domains)
    '*.ngrok.app',
    // Cloudflare Quick Tunnels (no account required)
    '*.trycloudflare.com',
    // Serveo (SSH-based tunneling)
    '*.serveo.net',
    // Localtunnel.
    '*.localtunnel.me',
    // Localtunnel (short alias)
    '*.loca.lt',
    // Pagekite.
    '*.pagekite.me',
    // localhost.run (SSH tunneling)
    '*.localhost.run',

    // -------------------------------------------------------------------------
    // Cloud / browser IDEs (generate per-workspace public URLs)
    // -------------------------------------------------------------------------
    // Gitpod cloud workspaces
    '*.gitpod.io',
    // GitHub Codespaces browser editor.
    '*.github.dev',
    // GitHub Codespaces forwarded ports.
    '*.app.github.dev',
    '*.preview.app.github.dev',

  ];

  /**
   * Constructs a new LicenseManager.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected KeyValueFactoryInterface $keyValueFactory,
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected TimeInterface $time,
    protected ModuleExtensionList $moduleList,
    protected LanguageManagerInterface $languageManager,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
    protected UuidInterface $uuidService,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Returns the KeyValue store for license credentials and metadata.
   */
  public function licenseStore(): KeyValueStoreInterface {
    return $this->keyValueFactory->get(self::LICENSE_COLLECTION);
  }

  /**
   * TRUE if the current request appears to be from a dev/test environment.
   *
   * Matches DEV_PATTERNS (glob), user-configured patterns, and raw IPv4.
   */
  public function isDevEnvironment(): bool {
    $host = $this->requestStack->getCurrentRequest()?->getHost() ?? '';
    if (empty($host)) {
      return FALSE;
    }

    // Raw IPv4 address (e.g. 192.168.1.10).
    if (preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $host)) {
      return TRUE;
    }

    $patterns = self::DEV_PATTERNS;
    $userPatterns = $this->configFactory->get('editoria11y_csa.settings')->get('dev_domains') ?? '';
    if (!empty($userPatterns)) {
      foreach (explode("\n", $userPatterns) as $line) {
        $line = trim($line);
        if ($line !== '') {
          $patterns[] = $line;
        }
      }
    }

    foreach ($patterns as $pattern) {
      // @todo test this and consider user validation.
      $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
      if (preg_match($regex, $host)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns TRUE if a Freemius API check should be performed on this cron run.
   */
  public function shouldCheck(): bool {
    if ($this->isDevEnvironment()) {
      return FALSE;
    }

    $status = CSAStatus::current($this->state);

    if (!$status->isLicensed()) {
      return FALSE;
    }

    if (!$this->licenseStore()->get('api_token')) {
      return FALSE;
    }

    $now = $this->time->getRequestTime();
    $lastCheck = $this->state->get(self::STATE_LAST_CHECK, 0);
    $isExpired = $status === CSAStatus::LicenseExpired;
    $interval = $isExpired ? self::INTERVAL_EXPIRED : self::INTERVAL_ACTIVE;

    if (($now - $lastCheck) >= $interval) {
      return TRUE;
    }

    // If active and the stored expiration date has been crossed since the last
    // check, trigger an immediate check even if the weekly interval hasn't
    // elapsed yet.
    if (!$isExpired) {
      $expiration = $this->licenseStore()->get('expiration');
      if ($expiration) {
        $expirationTime = strtotime($expiration);
        if ($expirationTime !== FALSE && $now >= $expirationTime && $lastCheck < $expirationTime) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Calls the Freemius API, applies the result, and returns the status.
   *
   * @return array{
   *   status: 'active'|'expired',
   *   expiration: string|null,
   *   is_cancelled: bool,
   *   license_id: int|null,
   *   }
   *
   * @throws \Drupal\editoria11y_csa\Exception\LicenseManagerException
   *   If credentials are missing or the API request fails.
   */
  public function checkStatus(): array {
    $store = $this->licenseStore();
    $installId = $store->get('install_id');
    $uuid = $store->get('uuid');
    $licenseKey = $store->get('license_key');
    $installApiToken = $store->get('api_token');

    if (!$licenseKey || !$installApiToken) {
      throw new LicenseManagerException('License credentials not found.');
    }

    try {
      $response = $this->httpClient->request('GET',
        'https://api.freemius.com/v1/products/' . self::FREEMIUS_PRODUCT_ID . '/installs/' . $installId . '/license.json',
        [
          'query' => [
            'uid' => $uuid,
            'license_key' => $licenseKey,
          ],
          'headers' => [
            'Authorization' => 'Bearer ' . $installApiToken,
          ],
          'timeout' => 10,
        ]
      );
    }
    catch (TransferException $e) {
      $response = $e instanceof RequestException ? $e->getResponse() : NULL;
      if ($response !== NULL) {
        $statusCode = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), TRUE);
        $apiMessage = is_array($body) ? ($body['error']['message'] ?? $body['message'] ?? '') : '';
        throw new LicenseManagerException("HTTP {$statusCode}: {$apiMessage}", 0, $e);
      }
      throw new LicenseManagerException('Licensing server did not respond, please try again later or contact support@editoria11y.com.', 0, $e);
    }

    if ($response->getStatusCode() !== 200) {
      throw new LicenseManagerException('Unexpected response code: ' . $response->getStatusCode());
    }

    $data = json_decode((string) $response->getBody(), TRUE);

    if (!is_array($data)) {
      throw new LicenseManagerException('Licensing server returned an unexpected response (non-JSON).');
    }

    $expiration = $data['expiration'] ?? NULL;
    $isCancelled = !empty($data['is_cancelled']);
    $now = $this->time->getRequestTime();

    $isExpired = $isCancelled || ($expiration !== NULL && strtotime($expiration) < $now);

    $result = [
      'status' => $isExpired ? 'expired' : 'active',
      'expiration' => $expiration,
      'is_cancelled' => $isCancelled,
      'license_id' => isset($data['id']) ? (int) $data['id'] : NULL,
    ];

    $this->applyResult($result);
    return $result;
  }

  /**
   * Activates a license key against the Freemius API.
   *
   * Performs the full activation flow: API call, save keys, set state,
   * and check license status.
   *
   * @param string $licenseKey
   *   The Freemius license key to activate.
   *
   * @return array
   *   The license status array from checkStatus().
   *
   * @throws \Drupal\editoria11y_csa\Exception\LicenseManagerException
   *   If the API request fails or the response is invalid.
   */
  public function activateLicense(string $licenseKey): array {
    $uuid = str_replace('-', '', $this->uuidService->generate());
    $title = $this->isDevEnvironment() ? 'DEV: ' : '';
    $title .= $this->configFactory->get('system.site')->get('name');

    try {
      $response = $this->httpClient->request('POST',
        'https://api.freemius.com/v1/products/' . self::FREEMIUS_PRODUCT_ID . '/licenses/activate.json',
        [
          'query' => [
            'uid' => $uuid,
            'license_key' => $licenseKey,
            'title' => $title,
            'url' => $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '',
          ],
          'timeout' => 60,
        ]
      );
    }
    catch (TransferException $e) {
      $response = $e instanceof RequestException ? $e->getResponse() : NULL;
      if ($response === NULL) {
        throw new LicenseManagerException('Licensing server did not respond. Please try again later.', 0, $e);
      }
      $statusCode = $response->getStatusCode();
      $body = json_decode((string) $response->getBody(), TRUE);
      $apiMessage = is_array($body) ? ($body['error']['message'] ?? $body['message'] ?? '') : '';
      $apiMessage = str_replace(" please contact support", ", check which sites are activated in the Subscription management portal at customers.freemius.com/login, or contact support", $apiMessage);
      throw new LicenseManagerException("Activation failed ({$statusCode}): {$apiMessage}", 0, $e);
    }

    $data = json_decode((string) $response->getBody(), TRUE);

    if (empty($data['install_id']) || empty($data['install_api_token'])) {
      throw new LicenseManagerException('Activation failed: the server returned an unexpected response.');
    }

    $store = $this->licenseStore();
    $store->set('license_key', $licenseKey);
    $store->set('api_token', $data['install_api_token']);
    $store->set('uuid', $uuid);
    $store->set('install_id', (string) $data['install_id']);
    $store->set('trial', !empty($data['trial']));
    $store->set('trial_ends_at', $data['trial_ends_at'] ?? NULL);
    $this->state->set(CSAStatus::STATE_KEY, CSAStatus::Licensed->value);

    // The activation API does not return expiration or license details.
    // Immediately check the license endpoint to populate them.
    $result = $this->checkStatus();

    Cache::invalidateTags(['config:editoria11y.settings']);

    return $result;
  }

  /**
   * Deactivates the current license.
   *
   * @param bool $notifyFreemius
   *   Whether to send a deactivation request to the Freemius API.
   */
  public function deactivateLicense(bool $notifyFreemius = TRUE): void {
    $store = $this->licenseStore();
    $remoteError = NULL;

    if ($notifyFreemius) {
      $licenseKey = $store->get('license_key') ?? '';

      try {
        $this->httpClient->request('POST',
          'https://api.freemius.com/v1/products/' . self::FREEMIUS_PRODUCT_ID . '/licenses/deactivate.json',
          [
            'query' => [
              'uid' => $store->get('uuid'),
              'install_id' => $store->get('install_id'),
              'license_key' => $licenseKey,
            ],
            'timeout' => 60,
          ]
        );
      }
      catch (TransferException $e) {
        $remoteError = $e;
        $this->logger->warning(
          'Freemius deactivation API call failed: @msg',
          ['@msg' => $e->getMessage()]
        );
      }
    }

    // Delete credentials and metadata; retain the license key for reactivation.
    $store->delete('api_token');
    $store->delete('uuid');
    $store->delete('install_id');
    $store->delete('expiration');
    $store->delete('trial');
    $store->delete('trial_ends_at');
    $store->delete('license_id');
    $this->state->set(CSAStatus::STATE_KEY, CSAStatus::Off->value);

    Cache::invalidateTags(['config:editoria11y.settings']);

    if ($remoteError !== NULL) {
      $siteName = (string) $this->configFactory->get('system.site')->get('name');
      throw new LicenseManagerException(
        'Error: licensing server did not respond to the deactivation request. Search for a site named "' . $siteName . '" for manual deactivation at customers.freemius.com/login.',
        0,
        $remoteError
      );
    }
  }

  /**
   * Cancels a Freemius subscription.
   *
   * Failures are logged but are non-fatal.
   *
   * @param int $licenseId
   *   The Freemius license ID whose subscription should be cancelled.
   */
  public function cancelSubscription(int $licenseId): void {
    $installApiToken = $this->licenseStore()->get('api_token');
    if (!$installApiToken) {
      $this->logger->warning('Cannot cancel subscription: API token not found.');
      return;
    }
    try {
      $this->httpClient->request('DELETE',
        'https://api.freemius.com/v1/products/' . self::FREEMIUS_PRODUCT_ID . '/licenses/' . $licenseId . '/subscription.json',
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $installApiToken,
          ],
          'timeout' => 60,
        ]
      );
    }
    catch (TransferException $e) {
      $this->logger->warning(
        'Freemius subscription cancellation API call failed: @msg',
        ['@msg' => $e->getMessage()]
      );
    }
  }

  /**
   * Retrieves the stored license key value.
   *
   * Falls back to a key named self::KEY_FALLBACK_ID provided by the optional
   * Key module when nothing is stored locally. The local keyValue entry always
   * wins so that a value explicitly set via the GUI or drush is never
   * overridden by a stale Key entity.
   *
   * @return string
   *   The stored license key.
   *
   * @throws \Drupal\editoria11y_csa\Exception\LicenseManagerException
   *   If no key is stored.
   */
  public function getStoredLicenseKey(): string {
    $key = $this->licenseStore()->get('license_key');
    if (!$key) {
      $key = $this->getLicenseKeyFromKeyModule();
    }
    if (!$key) {
      throw new LicenseManagerException('No stored license key found.');
    }
    return $key;
  }

  /**
   * Returns TRUE if a license key is available from any source.
   *
   * Checks the local keyValue store first, then the Key module fallback.
   */
  public function hasStoredLicenseKey(): bool {
    if ($this->licenseStore()->get('license_key')) {
      return TRUE;
    }
    return $this->getLicenseKeyFromKeyModule() !== NULL;
  }

  /**
   * Retrieves a license key from the Key module, if available.
   *
   * Key is a soft optional dependency; the repository service is resolved at
   * runtime to avoid requiring the module to be installed. Any failure in the
   * Key provider is logged and treated as "no key available" so that a
   * misconfigured provider cannot break the settings form or drush commands.
   */
  private function getLicenseKeyFromKeyModule(): ?string {
    if (!$this->moduleHandler->moduleExists('key')) {
      return NULL;
    }
    try {
      /** @var \Drupal\key\KeyRepositoryInterface $repository */
      // @phpstan-ignore-next-line
      $repository = \Drupal::service('key.repository');
      $keyEntity = $repository->getKey(self::KEY_FALLBACK_ID);
      if ($keyEntity === NULL) {
        return NULL;
      }
      $value = $keyEntity->getKeyValue();
      return is_string($value) && $value !== '' ? $value : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Key module lookup for @id failed: @msg', [
        '@id' => self::KEY_FALLBACK_ID,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Stores a license key for later activation.
   *
   * @param string $licenseKey
   *   The Freemius license key to store.
   */
  public function saveLicenseKey(string $licenseKey): void {
    $this->licenseStore()->set('license_key', $licenseKey);
  }

  /**
   * Returns TRUE if the trial period is still active.
   */
  public function isTrialActive(): bool {
    $started = $this->state->get('editoria11y_csa.trial_started');
    if (!$started) {
      return FALSE;
    }
    return ($this->time->getRequestTime() - $started) < self::TRIAL_DURATION;
  }

  /**
   * Returns the number of days remaining in the trial period.
   */
  public function getTrialDaysRemaining(): int {
    $started = $this->state->get('editoria11y_csa.trial_started');
    if (!$started) {
      return 0;
    }
    $remaining = self::TRIAL_DURATION - ($this->time->getRequestTime() - $started);
    return max(0, (int) ceil($remaining / 86400));
  }

  /**
   * Expires the trial if the trial period has elapsed.
   *
   * @return bool
   *   TRUE if the trial was expired by this call, FALSE otherwise.
   */
  public function expireTrialIfNeeded(): bool {
    $status = CSAStatus::current($this->state);
    if ($status !== CSAStatus::Trial) {
      return FALSE;
    }
    if (!$this->isTrialActive()) {
      $this->state->set(CSAStatus::STATE_KEY, CSAStatus::Off->value);
      Cache::invalidateTags(['config:editoria11y.settings']);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Sends current site and module metadata to Freemius as install properties.
   *
   * Called after a successful license check and after initial activation.
   * Failures are logged but never surfaced to the user — this is non-critical.
   */
  public function updateInstall(): void {
    if ($this->isDevEnvironment()) {
      return;
    }

    $store = $this->licenseStore();
    $installId = $store->get('install_id');
    $installApiToken = $store->get('api_token');

    if (!$installId || !$installApiToken) {
      return;
    }
    $moduleInfo = $this->moduleList->getExtensionInfo('editoria11y');

    $properties = [
      'version' => $moduleInfo['version'] ?? '3.0.x-dev',
      'title' => $this->configFactory->get('system.site')->get('name'),
      'url' => $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '',
      'language' => $this->languageManager->getDefaultLanguage()->getId(),
      'platform_version' => \Drupal::VERSION,
      'sdk_version' => '1.0.0',
    ];

    try {
      $this->httpClient->request('PUT',
        'https://api.freemius.com/v1/installs/' . $installId . '.json',
        [
          'json' => $properties,
          'headers' => [
            'Authorization' => 'Bearer ' . $installApiToken,
          ],
          'timeout' => 10,
        ]
      );
    }
    catch (TransferException $e) {
      $this->logger->warning(
        'Failed to update Freemius install properties: @msg',
        ['@msg' => $e->getMessage()]
      );
    }
  }

  /**
   * Applies a checkStatus() result to state.
   */
  private function applyResult(array $result): void {
    $this->state->set(self::STATE_LAST_CHECK, $this->time->getRequestTime());

    $newStatus = $result['status'] === 'active' ? CSAStatus::Licensed : CSAStatus::LicenseExpired;
    $this->state->set(CSAStatus::STATE_KEY, $newStatus->value);

    $store = $this->licenseStore();
    $store->set('expiration', $result['expiration']);
    if (!empty($result['license_id'])) {
      $store->set('license_id', $result['license_id']);
    }

    Cache::invalidateTags(['config:editoria11y.settings']);

    if ($result['status'] === 'active') {
      $this->updateInstall();
    }
  }

}
