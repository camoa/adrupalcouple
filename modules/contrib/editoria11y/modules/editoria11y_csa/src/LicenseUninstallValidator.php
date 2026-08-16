<?php

namespace Drupal\editoria11y_csa;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editoria11y\CSAStatus;
use Drupal\Core\Url;

/**
 * Prevents uninstalling editoria11y_csa while a license is active.
 */
class LicenseUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Constructs a LicenseUninstallValidator.
   */
  public function __construct(
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function validate($module): array {
    $reasons = [];
    if ($module === 'editoria11y_csa') {
      $status = CSAStatus::current($this->state);
      if ($status->isLicensed()) {
        $settings = Url::fromRoute('editoria11y_csa.settings')->toString();
        $reasons[] = $this->t('An <a href="@settings">active license must be deactivated</a> before uninstalling.', ['@settings' => $settings]);
      }
    }
    return $reasons;
  }

}
