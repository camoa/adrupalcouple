<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;

/**
 * Configure Schema.org properties settings for properties.
 */
class SchemaDotOrgSettingsPropertiesForm extends SchemaDotOrgSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_properties_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['schema_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Property settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schema_properties']['default_fields'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      '#settings_format' => 'SchemaType--propertyName|type:string,label:Property name,unlimited:1,required:1 or propertyName|type:string',
      '#title' => $this->t('Default Schema.org property fields'),
      '#rows' => 20,
      '#description' => $this->t('Enter default Schema.org property field definition used when adding a Schema.org property to an entity type.'),
      '#description_link' => 'properties',
    ];
    $form['schema_properties']['default_field_formatter_settings'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::ASSOCIATIVE_GROUPED,
      '#settings_format' => 'SchemaType--propertyName|label:hidden or propertyName|label:hidden',
      '#title' => $this->t('Default Schema.org property field formatter settings'),
      '#rows' => 20,
      '#description' => $this->t('Enter default Schema.org property field formatter settings used when adding a Schema.org property to an entity type.'),
      '#description_link' => 'properties',
    ];
    $form['schema_properties']['default_field_types'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'schemaProperty|field_type_01,field_type_02,field_type_03 or SchemaType--schemaProperty|field_type_01,field_type_02,field_type_03',
      '#title' => $this->t('Default Schema.org property field types'),
      '#description' => $this->t('Enter the field types applied to a Schema.org property when the property is added to an entity type.'),
      '#description_link' => 'properties',
    ];
    $form['schema_properties']['default_field_weights'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Default Schema.org property field weights'),
      '#description' => $this->t('Enter Schema.org property default field weights to help organize fields as they are added to entity types.'),
    ];
    $form['schema_properties']['range_includes'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED,
      '#settings_format' => 'TypeName--propertyName|Type01,Type02 or propertyName|Type01,Type02',
      '#title' => $this->t('Schema.org type/property custom range includes'),
      '#description' => $this->t('Enter custom range includes for Schema.org types/properties.'),
      '#description_link' => 'types',
    ];
    $form['schema_properties']['ignored_properties'] = [
      '#type' => 'schemadotorg_settings',
      '#settings_type' => SchemaDotOrgSettings::INDEXED,
      '#title' => $this->t('Ignored Schema.org properties'),
      '#description' => $this->t('Enter Schema.org properties that should ignored and not displayed on the Schema.org mapping form and simplifies the user experience.'),
      '#description_link' => 'properties',
    ];
    return parent::buildForm($form, $form_state);
  }

}
