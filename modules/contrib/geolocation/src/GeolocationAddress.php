<?php

namespace Drupal\geolocation;

/**
 * DTO.
 */
class GeolocationAddress {

  public function __construct(
    public string $organization = '',
    public string $addressLine1 = '',
    public string $addressLine2 = '',
    public string $addressLine3 = '',
    public string $dependentLocality = '',
    public string $locality = '',
    public string $administrativeArea = '',
    public string $postalCode = '',
    public string $sortingCode = '',
    public string $countryCode = '',
  ) {}

  /**
   * Format address as string.
   *
   * @return string
   *   Address.
   */
  public function __toString(): string {
    return implode(' ', array_filter([
      $this->organization,
      $this->addressLine1,
      $this->addressLine2,
      $this->addressLine3,
      $this->dependentLocality,
      $this->locality,
      $this->administrativeArea,
      $this->postalCode,
      $this->sortingCode,
    ]));
  }

}
