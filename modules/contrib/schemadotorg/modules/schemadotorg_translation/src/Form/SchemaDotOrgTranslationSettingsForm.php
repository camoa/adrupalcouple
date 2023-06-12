<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_translation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Drupal\schemadotorg\Form\SchemaDotOrgSettingsFormBase;

/**
 * Configure Schema.org Translation settings.
 */
class SchemaDotOrgTranslationSettingsForm extends SchemaDotOrgSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_translation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_translation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['schemadotorg_translation'] = [
      '#type' => 'details',
      '#title' => $this->t('Translation settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_translation']['excluded_schema_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded Schema.org types'),
      '#description' => $this->t('Enter Schema.org types that should never be translated.'),
      '#description_link' => 'types',
    ];
    $form['schemadotorg_translation']['excluded_schema_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded Schema.org properties'),
      '#settings_format' => 'propertyName or SchemaType--propertyName',
      '#description' => $this->t('Enter Schema.org properties that should never be translated.'),
      '#description_link' => 'properties',
    ];
    $form['schemadotorg_translation']['excluded_field_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Excluded field names'),
      '#description' => $this->t('Enter field names that should never be translated.'),
    ];
    $form['schemadotorg_translation']['included_field_names'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Included field names'),
      '#description' => $this->t('Enter field names that should always be translated.'),
    ];
    $form['schemadotorg_translation']['included_field_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Included field types'),
      '#description' => $this->t('Enter field types that should always be translated.'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
