<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Drupal\schemadotorg\Form\SchemaDotOrgSettingsFormBase;

/**
 * Configure Schema.org JSON-LD settings.
 */
class SchemaDotOrgJsonLdSettingsForm extends SchemaDotOrgSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_jsonld_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_jsonld.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['schemadotorg_jsonld'] = [
      '#type' => 'details',
      '#title' => $this->t('JSON-LD settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schemadotorg_jsonld']['property_order'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#settings_format' => 'propertyName',
      '#title' => $this->t('Schema.org property order'),
      '#description' => $this->t('Enter the default Schema.org property order.'),
      '#description_link' => 'properties',
    ];
    $form['schemadotorg_jsonld']['property_image_styles'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
      '#settings_format' => 'propertyName|image_style',
      '#title' => $this->t('Schema.org property image styles'),
      '#description' => $this->t('Enter the Schema.org property and the desired image style.'),
      '#description_link' => 'properties',
    ];
    return parent::buildForm($form, $form_state);
  }

}
