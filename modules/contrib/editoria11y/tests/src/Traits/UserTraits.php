<?php

namespace Drupal\Tests\editoria11y\Traits;

/**
 * Traits the new user.
 */
trait UserTraits {

  /**
   * Views aggregation causes intractable incorrect schema errors.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore

  /**
   * Define a new administrator user.
   */
  public function setUpAdmin() : void {
    // $user =
  }

}
