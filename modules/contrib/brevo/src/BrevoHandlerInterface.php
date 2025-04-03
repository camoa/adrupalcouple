<?php

namespace Drupal\brevo;

/**
 * The interface for Brevo handler service.
 */
interface BrevoHandlerInterface {

  const CONFIG_NAME = 'brevo.settings';

  /**
   * Validates Brevo library and API settings.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether the library installed and API settings are ok.
   */
  public function moduleStatus($showMessage = FALSE);

  /**
   * Validates Brevo API key.
   *
   * @param string $key
   *   The API key.
   *
   * @return bool
   *   Whether the API key is valid.
   */
  public function validateBrevoApiKey($key);

  /**
   * Get the Brevo account information.
   *
   * @param string $key
   *   The API key.
   *
   * @return ?array
   *   Account information, or null if there is an issue.
   *
   * @throws \Brevo\Client\ApiException
   */
  public function getBrevoAccount($key);

  /**
   * Checks if API settings are correct and not empty.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether API settings are valid.
   */
  public function validateBrevoApiSettings($showMessage = FALSE);

  /**
   * Checks if Brevo PHP SDK is installed correctly.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether the Brevo PHP SDK is installed correctly.
   */
  public function validateBrevoLibrary($showMessage = FALSE);

}
