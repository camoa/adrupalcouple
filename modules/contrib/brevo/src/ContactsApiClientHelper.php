<?php

namespace Drupal\brevo;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\CreateDoiContact;
use Brevo\Client\Model\UpdateContact;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Helper class for interacting with the Brevo Contacts API.
 */
class ContactsApiClientHelper {

  /**
   * The (lazy loaded) contactsApi api client.
   *
   * @var ?\Brevo\Client\Api\ContactsApi
   */
  protected ?ContactsApi $contactsApiClient;

  /**
   * Constructs a ContactsApiClientHelper object.
   *
   * @param \Drupal\brevo\BrevoFactory $brevoFactory
   *   The Brevo factory service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected BrevoFactory $brevoFactory,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * Get lazy-loaded contacts api client.
   *
   * We need to lazy load it, as if we initialize it on construction an error
   * occurs.
   *
   * @return \Brevo\Client\Api\ContactsApi
   *   The Brevo Contacts API client.
   *
   * @throws \RuntimeException
   *   If the Brevo library is not installed.
   */
  protected function contactsApiClient(): ContactsApi {
    if (!isset($this->contactsApiClient)) {
      $this->contactsApiClient = $this->brevoFactory->createContactsApiClient();
      if ($this->contactsApiClient === NULL) {
        throw new \RuntimeException('Brevo library is not installed.');
      }
    }
    return $this->contactsApiClient;
  }

  /**
   * Retrieves contact information by email.
   *
   * @param string $email
   *   The email address of the contact.
   *
   * @return mixed|null
   *   The contact information or NULL if not found.
   */
  public function getContactInfo($email) {
    try {
      $contactsApi = $this->contactsApiClient();
      // The API states, that the email should be URL-Encoded, but this is not
      // true, so we pass it as is:
      // @see https://github.com/getbrevo/brevo-php/blob/main/docs/Api/ContactsApi.md#getcontactinfo
      $contactInfo = $contactsApi->getContactInfo($email, 'email_id');
      return $contactInfo;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Creates a new contact.
   *
   * @param string $email
   *   The email address of the contact.
   * @param array $listIds
   *   The list IDs to associate with the contact.
   */
  public function createContact(string $email, array $listIds = []) {
    $contactsApi = $this->contactsApiClient();
    try {
      $contact = new CreateContact(array_merge(
        ['email' => $email],
        !empty($listIds) ? ['listIds' => $listIds] : []
      ));
      $contactsApi->createContact($contact);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create contact @email: @message', [
        '@email' => $email,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Update contact by mail.
   *
   * @param string $email
   *   The email address of the contact.
   * @param array $listIds
   *   The list ids to update.
   */
  public function updateContact(string $email, array $listIds = []) {
    $contactsApi = $this->contactsApiClient();
    try {
      $contactData = new UpdateContact(['listIds' => $listIds]);
      // The API states, that the email should be URL-Encoded, but this is not
      // true, so we pass it as is:
      // @see https://github.com/getbrevo/brevo-php/blob/main/docs/Api/ContactsApi.md#updatecontact
      $contactsApi->updateContact($contactData, $email, 'email_id');
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to update contact @email: @message', [
        '@email' => $email,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Creates a double-opt-in contact.
   *
   * Note, that the underlying API call differs from "createContact". This
   * method additionally updates the contact's list IDs.
   *
   * @param string $email
   *   The email address of the contact.
   * @param string $redirectionUrl
   *   The URL to redirect after confirmation.
   * @param int $templateId
   *   The template ID for the confirmation email.
   * @param array $listIds
   *   The list IDs to include.
   */
  public function createDoiContact(string $email, string $redirectionUrl, int $templateId, array $listIds = []) {
    $contactsApi = $this->contactsApiClient();
    try {
      $contact = new CreateDoiContact(array_merge(
        [
          'email' => $email,
          'redirectionUrl' => $redirectionUrl,
          'templateId' => $templateId,
        ],
        !empty($listIds) ? ['includeListIds' => $listIds] : []
      ));
      $contactsApi->createDoiContact($contact);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create double-opt-in contact @email: @message', [
        '@email' => $email,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Fetches available contact lists.
   *
   * @return array
   *   An array of available lists or an empty array, if an error occurs.
   */
  public function fetchAvailableLists(): array {
    try {
      $lists = $this->contactsApiClient()->getLists()?->getLists() ?? [];
      // Convert SDK model objects to arrays for config storage.
      return array_map(fn($list) => json_decode(json_encode($list), TRUE), $lists);
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
