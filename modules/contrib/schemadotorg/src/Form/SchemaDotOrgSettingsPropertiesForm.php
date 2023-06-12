<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org properties settings for properties.
 */
class SchemaDotOrgSettingsPropertiesForm extends SchemaDotOrgSettingsFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

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
    $config = $this->config('schemadotorg.settings');

    $form['schema_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Property settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['schema_properties']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schema.org property field prefix'),
      '#description' => $this->t('Enter the field prefix to be prepended to a Schema.org property when added to an entity type.')
      . ' '
      . $this->t('Schema.org property field prefix cannot be updated after mappings have been created.'),
      '#default_value' => $config->get('field_prefix'),
      '#parents' => ['field_prefix'],
    ];
    if ($this->entityTypeManager->getStorage('schemadotorg_mapping')->loadMultiple()) {
      $form['schema_properties']['field_prefix']['#disabled'] = TRUE;
      $form['schema_properties']['field_prefix']['#value'] = $config->get('field_prefix');
    }
    $t_args = [
      '%drupal_field_prefix' => $this->configFactory()->get('field_ui.settings')->get('field_prefix') ?? 'field_',
      '%schemadotorg_field_prefix' => $this->configFactory()->get('schemadotorg.settings')->get('field_prefix'),
    ];
    $form['schema_properties']['field_prefix_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the Schema.org field prefix to be selected via the field UI.'),
      '#description' => $this->t("If checked, site builders will be able to select between the Drupal's field prefix (%drupal_field_prefix) or the Schema.org Blueprints' field prefix (%schemadotorg_field_prefix) when adding new fields.", $t_args),
      '#return_value' => TRUE,
      '#parents' => ['field_prefix_ui'],
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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('schemadotorg.settings')
      ->set('field_prefix', $form_state->getValue('field_prefix'))
      ->set('field_prefix_ui', $form_state->getValue('field_prefix_ui'))
      ->set('schema_properties', $form_state->getValue('schema_properties'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
