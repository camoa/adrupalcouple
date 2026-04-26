<?php

namespace Drupal\brevo_mailer;

/**
 * The interface for Brevo Mailer handler service.
 */
interface BrevoMailerHandlerInterface {

  const CONFIG_NAME = 'brevo_mailer.settings';

  /**
   * Connects to Brevo API and sends out the email.
   *
   * @param array $brevoMessage
   *   A message array, as described in
   *   https://developers.brevo.com/docs/send-a-transactional-email.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted by the API, FALSE otherwise.
   *
   * @see https://developers.brevo.com/docs/send-a-transactional-email
   */
  public function sendMail(array $brevoMessage);

  /**
   * Checks if Brevo's drupal dependencies are properly installed.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether Drupal modules dependencies are properly installed.
   */
  public function validateDrupalMailerLibrary($showMessage = FALSE);

  /**
   * Checks if Brevo's Drupal mail modules configuration is properly set.
   *
   * @param bool $showMessage
   *   Whether error messages should be shown.
   *
   * @return bool
   *   Whether Brevo's Drupal mail modules configuration is properly set.
   */
  public function validateDrupalMailerConfiguration($showMessage = FALSE);

}
