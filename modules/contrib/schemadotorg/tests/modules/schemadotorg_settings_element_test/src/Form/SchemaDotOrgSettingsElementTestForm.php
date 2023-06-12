<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_settings_element_test\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Provides a Scheme.org Blueprint Settings Element test form.
 */
class SchemaDotOrgSettingsElementTestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['schemadotorg_settings_element_test.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_settings_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['schemadotorg_settings_element_test'] = [
      '#tree' => TRUE,
    ];

    // Create examples of all settings types.
    $settings_types = [
      SchemaDotOrgSettings::INDEXED,
      SchemaDotOrgSettings::INDEXED_GROUPED,
      SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      SchemaDotOrgSettings::ASSOCIATIVE,
      SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      SchemaDotOrgSettings::ASSOCIATIVE_GROUPED_NAMED,
      SchemaDotOrgSettings::LINKS,
      SchemaDotOrgSettings::LINKS_GROUPED,
      SchemaDotOrgSettings::YAML,
    ];
    foreach ($settings_types as $settings_type) {
      $form['schemadotorg_settings_element_test'][$settings_type] = [
        '#type' => 'schemadotorg_settings',
        '#title' => $settings_type,
        '#settings_type' => $settings_type,
      ];
    }

    // Add 'Browse Schema.org types.' to the first element.
    $form['schemadotorg_settings_element_test'][SchemaDotOrgSettings::INDEXED]['#description_link'] = 'types';

    // Add advanced associate settings with mapping.
    $form['schemadotorg_settings_element_test']['associative_advanced'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => 'associative_advanced',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE,
    ];

    // Add advanced associate settings with mapping.
    $form['schemadotorg_settings_element_test']['associative_grouped_invalid'] = [
      '#type' => 'schemadotorg_settings',
      '#title' => 'associative_grouped_invalid',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save settings.
    $settings = $form_state->getValue('schemadotorg_settings_element_test');
    $this->config('schemadotorg_settings_element_test.settings')
      ->setData($settings)
      ->save();

    // Display the updated settings.
    $this->messenger()->addStatus(Markup::create('<pre>' . Yaml::encode($settings) . '</pre>'));
  }

}
