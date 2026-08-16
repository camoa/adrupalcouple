<?php

declare(strict_types=1);

namespace Drupal\editoria11y;

use Drupal\Core\State\StateInterface;

/**
 * Allowed values for the editoria11y_csa.activation_status state.
 */
enum CSAStatus: string {

  /**
   * The state key where the activation status is stored.
   */
  const STATE_KEY = 'editoria11y_csa.activation_status';

  case Off = 'off';
  case Trial = 'trial';
  case Licensed = 'licensed';
  case LicenseExpired = 'license_expired';

  // Form-only value: activate with a new license key.
  case Activate = 'activate';

  // Form-only value: activate using a previously stored key.
  case ActivateStored = 'activate_stored';

  /**
   * Loads the current CSA status from state.
   */
  public static function current(StateInterface $state): self {
    return self::tryFrom(
      $state->get(self::STATE_KEY, self::Off->value)
    ) ?? self::Off;
  }

  /**
   * Returns TRUE if CSA features should be enabled for this status.
   */
  public function isActive(): bool {
    return in_array($this, [self::Trial, self::Licensed]);
  }

  /**
   * Returns TRUE if this status represents a Freemius license state.
   */
  public function isLicensed(): bool {
    return in_array($this, [self::Licensed, self::LicenseExpired]);
  }

}
