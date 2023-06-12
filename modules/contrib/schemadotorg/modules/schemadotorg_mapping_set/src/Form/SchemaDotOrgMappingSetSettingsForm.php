<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_mapping_set\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\schemadotorg\Element\SchemaDotOrgSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Schema.org mapping set settings.
 */
class SchemaDotOrgMappingSetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'schemadotorg_mapping_set_settings_form';
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Schema.org schema type manager.
   *
   * @var \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface
   */
  protected $schemaTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->schemaTypeManager = $container->get('schemadotorg.schema_type_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['schemadotorg_mapping_set.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['sets'] = [
      '#type' => 'schemadotorg_settings',
      '#rows' => 12,
      '#settings_type' => SchemaDotOrgSettings::INDEXED_GROUPED_NAMED,
      '#settings_format' => 'set_name|Set label|entity_type_id:SchemaType01,entity_type_id:SchemaType02',
      '#array_name' => 'types',
      '#title' => $this->t('Mapping sets'),
      '#description' => $this->t('Enter Schema.org mapping sets by name, label, and entity type to Schema.org type.'),
      '#description_link' => 'types',
      '#config_name' => 'schemadotorg_mapping_set.settings',
      '#config_key' => 'sets',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getErrors()) {
      return;
    }

    $sets = $form_state->getValue('sets');
    foreach ($sets as $set) {
      foreach ($set['types'] as $type) {
        $t_args = ['%set' => $set['label']];
        if (!str_contains($type, ':')) {
          $t_args['%type'] = $type;
          $message = $this->t('%type in %set is not valid. Please enter the entity type id and Schema.org type (i.e. entity_type_id:SchemaType).', $t_args);
          $form_state->setErrorByName('sets', $message);
        }
        else {
          [$entity_type_id, $schema_type] = explode(':', $type);
          if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
            $t_args['%entity_type_id'] = $entity_type_id;
            $message = $this->t('%entity_type_id in %set is not valid entity type.', $t_args);
            $form_state->setErrorByName('sets', $message);
          }
          if (!$this->schemaTypeManager->isType($schema_type)) {
            $t_args['%schema_type'] = $schema_type;
            $message = $this->t('%schema_type in %set is not valid Schema.org type.', $t_args);
            $form_state->setErrorByName('sets', $message);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('schemadotorg_mapping_set.settings')
      ->set('sets', $form_state->getValue('sets'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
