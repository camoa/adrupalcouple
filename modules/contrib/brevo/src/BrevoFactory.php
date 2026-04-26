<?php

namespace Drupal\brevo;

use Brevo\Client\Api\AccountApi;
use Brevo\Client\Api\AttributesApi;
use Brevo\Client\Api\CompaniesApi;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Api\ConversationsApi;
use Brevo\Client\Api\CouponsApi;
use Brevo\Client\Api\CRMApi;
use Brevo\Client\Api\DealsApi;
use Brevo\Client\Api\DomainsApi;
use Brevo\Client\Api\EcommerceApi;
use Brevo\Client\Api\EmailCampaignsApi;
use Brevo\Client\Api\EventsApi;
use Brevo\Client\Api\ExternalFeedsApi;
use Brevo\Client\Api\FilesApi;
use Brevo\Client\Api\FoldersApi;
use Brevo\Client\Api\InboundParsingApi;
use Brevo\Client\Api\ListsApi;
use Brevo\Client\Api\MasterAccountApi;
use Brevo\Client\Api\NotesApi;
use Brevo\Client\Api\ProcessApi;
use Brevo\Client\Api\ResellerApi;
use Brevo\Client\Api\SendersApi;
use Brevo\Client\Api\SMSCampaignsApi;
use Brevo\Client\Api\TasksApi;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Api\TransactionalSMSApi;
use Brevo\Client\Api\TransactionalWhatsAppApi;
use Brevo\Client\Api\UserApi;
use Brevo\Client\Api\WebhooksApi;
use Brevo\Client\Api\WhatsAppCampaignsApi;
use Brevo\Client\Configuration;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Defines the brevo factory.
 */
class BrevoFactory {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $brevoConfig;

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs BrevoFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    $this->brevoConfig = $configFactory->get(BrevoHandlerInterface::CONFIG_NAME);
    $this->httpClient = $httpClient;
  }

  /**
   * Get the Brevo API client configuration.
   *
   * @param string|null $api_key
   *   Brevo API key, defaults to configured one.
   *
   * @return \Brevo\Client\Configuration
   *   Brevo PHP SDK configuration.
   */
  public function getConfiguration(?string $api_key = NULL) {
    $api_key = $api_key ?? (string) $this->brevoConfig->get('api_key');
    $config = Configuration::getDefaultConfiguration()
      ->setApiKey('api-key', $api_key);

    return $config;
  }

  /**
   * Create Brevo Account API client.
   *
   * @return \Brevo\Client\Api\AccountApi
   *   Brevo PHP SDK Account API client.
   */
  public function createAccountApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new AccountApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Attributes API client.
   *
   * @return \Brevo\Client\Api\AttributesApi
   *   Brevo PHP SDK Attributes API client.
   */
  public function createAttributesApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new AttributesApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Companies API client.
   *
   * @return \Brevo\Client\Api\CompaniesApi
   *   Brevo PHP SDK Companies API client.
   */
  public function createCompaniesApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new CompaniesApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Contacts API client.
   *
   * @return \Brevo\Client\Api\ContactsApi
   *   Brevo PHP SDK Contacts API client.
   */
  public function createContactsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ContactsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Conversations API client.
   *
   * @return \Brevo\Client\Api\ConversationsApi
   *   Brevo PHP SDK Conversations API client.
   */
  public function createConversationsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ConversationsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Coupons API client.
   *
   * @return \Brevo\Client\Api\CouponsApi
   *   Brevo PHP SDK Coupons API client.
   */
  public function createCouponsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new CouponsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo CRM API client.
   *
   * @return \Brevo\Client\Api\CRMApi|null
   *   Brevo PHP SDK CRM API client or NULL if library not installed.
   */
  public function createCRMApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new CRMApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Deals API client.
   *
   * @return \Brevo\Client\Api\DealsApi
   *   Brevo PHP SDK Deals API client.
   */
  public function createDealsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new DealsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Domains API client.
   *
   * @return \Brevo\Client\Api\DomainsApi
   *   Brevo PHP SDK Domains API client.
   */
  public function createDomainsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new DomainsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Ecommerce API client.
   *
   * @return \Brevo\Client\Api\EcommerceApi
   *   Brevo PHP SDK Ecommerce API client.
   */
  public function createEcommerceApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new EcommerceApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo EmailCampaigns API client.
   *
   * @return \Brevo\Client\Api\EmailCampaignsApi
   *   Brevo PHP SDK EmailCampaigns API client.
   */
  public function createEmailCampaignsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new EmailCampaignsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Events API client.
   *
   * @return \Brevo\Client\Api\EventsApi
   *   Brevo PHP SDK Events API client.
   */
  public function createEventsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new EventsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo ExternalFeeds API client.
   *
   * @return \Brevo\Client\Api\ExternalFeedsApi
   *   Brevo PHP SDK ExternalFeeds API client.
   */
  public function createExternalFeedsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ExternalFeedsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Files API client.
   *
   * @return \Brevo\Client\Api\FilesApi
   *   Brevo PHP SDK Files API client.
   */
  public function createFilesApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new FilesApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Folders API client.
   *
   * @return \Brevo\Client\Api\FoldersApi
   *   Brevo PHP SDK Folders API client.
   */
  public function createFoldersApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new FoldersApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo InboundParsing API client.
   *
   * @return \Brevo\Client\Api\InboundParsingApi
   *   Brevo PHP SDK InboundParsing API client.
   */
  public function createInboundParsingApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new InboundParsingApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Lists API client.
   *
   * @return \Brevo\Client\Api\ListsApi
   *   Brevo PHP SDK Lists API client.
   */
  public function createListsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ListsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo MasterAccount API client.
   *
   * @return \Brevo\Client\Api\MasterAccountApi
   *   Brevo PHP SDK MasterAccount API client.
   */
  public function createMasterAccountApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new MasterAccountApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Notes API client.
   *
   * @return \Brevo\Client\Api\NotesApi
   *   Brevo PHP SDK Notes API client.
   */
  public function createNotesApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new NotesApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Process API client.
   *
   * @return \Brevo\Client\Api\ProcessApi
   *   Brevo PHP SDK Process API client.
   */
  public function createProcessApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ProcessApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Reseller API client.
   *
   * @return \Brevo\Client\Api\ResellerApi
   *   Brevo PHP SDK Reseller API client.
   */
  public function createResellerApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new ResellerApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Senders API client.
   *
   * @return \Brevo\Client\Api\SendersApi
   *   Brevo PHP SDK Senders API client.
   */
  public function createSendersApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new SendersApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo SMSCampaigns API client.
   *
   * @return \Brevo\Client\Api\SMSCampaignsApi|null
   *   Brevo PHP SDK SMSCampaigns API client or NULL if library not installed.
   */
  public function createSMSCampaignsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new SMSCampaignsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Tasks API client.
   *
   * @return \Brevo\Client\Api\TasksApi
   *   Brevo PHP SDK Tasks API client.
   */
  public function createTasksApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new TasksApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Transactional emails API client.
   *
   * @return \Brevo\Client\Api\TransactionalEmailsApi
   *   Brevo PHP SDK Transactional emails API client.
   */
  public function createTransactionalEmailsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new TransactionalEmailsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo TransactionalSMS API client.
   *
   * @return \Brevo\Client\Api\TransactionalSMSApi|null
   *   Brevo PHP SDK TransactionalSMS API client, or NULL if not installed.
   */
  public function createTransactionalSMSApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new TransactionalSMSApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo TransactionalWhatsApp API client.
   *
   * @return \Brevo\Client\Api\TransactionalWhatsAppApi
   *   Brevo PHP SDK TransactionalWhatsApp API client.
   */
  public function createTransactionalWhatsAppApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new TransactionalWhatsAppApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo User API client.
   *
   * @return \Brevo\Client\Api\UserApi
   *   Brevo PHP SDK User API client.
   */
  public function createUserApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new UserApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo Webhooks API client.
   *
   * @return \Brevo\Client\Api\WebhooksApi
   *   Brevo PHP SDK Webhooks API client.
   */
  public function createWebhooksApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new WebhooksApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Create Brevo WhatsAppCampaigns API client.
   *
   * @return \Brevo\Client\Api\WhatsAppCampaignsApi
   *   Brevo PHP SDK WhatsAppCampaigns API client.
   */
  public function createWhatsAppCampaignsApiClient(?string $api_key = NULL) {
    if (!$this->isBrevoLibraryInstalled()) {
      return NULL;
    }

    return new WhatsAppCampaignsApi($this->httpClient, $this->getConfiguration($api_key));
  }

  /**
   * Check if Brevo library is installed.
   *
   * @return bool
   *   TRUE if the Brevo library is installed, FALSE otherwise.
   */
  public function isBrevoLibraryInstalled() {
    return class_exists('\Brevo\Client\Configuration');
  }

}
