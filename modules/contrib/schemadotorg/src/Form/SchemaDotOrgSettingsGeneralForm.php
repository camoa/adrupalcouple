<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

/**
 * Configure Schema.org general settings for types.
 */
class SchemaDotOrgSettingsGeneralForm extends SchemaDotOrgSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_general_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg.settings'];
  }

}
