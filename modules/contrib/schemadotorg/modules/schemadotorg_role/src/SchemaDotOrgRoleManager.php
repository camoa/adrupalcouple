<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_role;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface;
use Drupal\schemadotorg_field_group\SchemaDotOrgFieldGroupEntityDisplayBuilderInterface;

/**
 * Schema.org role manager.
 */
class SchemaDotOrgRoleManager implements SchemaDotOrgRoleManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgRoleManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirectDestination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager
   *   The Schema.org schema type manager.
   * @param \Drupal\schemadotorg\SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder
   *   The Schema.org entity type builder.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $mappingManager
   *   The Schema.org mapping manager.
   * @param \Drupal\schemadotorg_field_group\SchemaDotOrgFieldGroupEntityDisplayBuilderInterface|null $fieldGroupEntityDisplayBuilder
   *   The Schema.org field group entity display builder.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RedirectDestinationInterface $redirectDestination,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected SchemaDotOrgSchemaTypeManagerInterface $schemaTypeManager,
    protected SchemaDotOrgEntityTypeBuilderInterface $entityTypeBuilder,
    protected SchemaDotOrgMappingManagerInterface $mappingManager,
    protected ?SchemaDotOrgFieldGroupEntityDisplayBuilderInterface $fieldGroupEntityDisplayBuilder
  ) {}

  /**
   * {@inheritdoc}
   */
  public function mappingDefaultsAlter(array &$defaults, string $entity_type_id, ?string $bundle, string $schema_type): void {
    // Do not create properties that are using roles field.
    $field_definitions = $this->getSchemaTypeFieldDefinitions($schema_type);
    foreach ($field_definitions as $field_definition) {
      $schema_property = $field_definition['schema_property'];
      if (isset($defaults['properties'][$schema_property])
        && $defaults['properties'][$schema_property]['name'] === SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD) {
        $defaults['properties'][$schema_property]['name'] = NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingFormAlter(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\schemadotorg\Form\SchemaDotOrgMappingForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\schemadotorg\SchemaDotOrgMappingInterface $mapping */
    $mapping = $form_object->getEntity();

    // Exit if no Schema.org type has been selected.
    if (!$mapping->getSchemaType()) {
      return;
    }

    $field_definitions = $this->getMappingFieldDefinitions($mapping);
    if (!$field_definitions) {
      return;
    }

    foreach ($field_definitions as $field_definition) {
      $schema_property = $field_definition['schema_property'];
      $build_property =& $form['mapping']['properties'][$schema_property];

      // Build warning message once.
      if (!isset($build_property['#attributes'])) {
        $build_property['#attributes']['class'] = [
          $mapping->isNew() ? 'color-warning' : 'color-states',
        ];
        $t_args = [
          '%property' => $schema_property,
        ];
        $build_property['field'] = [];
        $build_property['field']['data'] = [];
        $build_property['field']['data']['description'] = [
          '#markup' => $this->t('The %property property is mapped to the below role-related fields.', $t_args),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
        $build_property['field']['data']['fields'] = [
          '#theme' => 'item_list',
          '#items' => [],
        ];
        $url = Url::fromRoute(
          'schemadotorg.settings.properties',
          [],
          [
            'query' => $this->redirectDestination->getAsArray(),
            'fragment' => 'edit-schemadotorg-role',
          ],
        );
        $build_property['field']['data']['edit'] = [
          '#type' => 'link',
          '#title' => $this->t('Edit settings'),
          '#url' => $url,
          '#attributes' => ['class' => ['button', 'button--small', 'button--extrasmall']],
        ];
      }

      // Append role field information to the warning message fields.
      $t_args = [
        '@label' => $field_definition['label'],
        '@name' => $field_definition['field_name'],
      ];
      $build_property['field']['data']['fields']['#items'][] = $this->t('@label (@name)', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mappingInsert(SchemaDotOrgMappingInterface $mapping): void {
    $schema_type = $mapping->getSchemaType();
    $entity_type_id = $mapping->getTargetEntityTypeId();
    $bundle = $mapping->getTargetBundle();

    // Build the field definition.
    $role_field_definitions = $this->getSchemaTypeFieldDefinitions($schema_type);
    $properties = [];
    foreach ($role_field_definitions as $role_field_definition) {
      $field_name = $role_field_definition['field_name'];
      $schema_property = $role_field_definition['schema_property'];

      $mapping_defaults = $this->mappingManager->getMappingDefaults($entity_type_id, $bundle, $schema_type);

      $field = $mapping_defaults['properties'][$schema_property];
      $field['machine_name'] = $role_field_definition['field_name'];
      $field['label'] = $role_field_definition['label'];
      $field['description'] = $role_field_definition['description'];
      $field['schema_type'] = $schema_type;
      $field['schema_property'] = $schema_property;

      $this->entityTypeBuilder->addFieldToEntity($entity_type_id, $bundle, $field);

      $properties[$field_name] = $schema_property;
    }

    // Set roles into field groups.
    if ($this->fieldGroupEntityDisplayBuilder) {
      $this->fieldGroupEntityDisplayBuilder->setFieldGroups($entity_type_id, $bundle, $schema_type, $properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaTypeFieldDefinitions(string $schema_type): array {
    $config = $this->configFactory->get('schemadotorg_role.settings');

    $field_definitions = [];

    $role_schema_types = $config->get('schema_types');
    $role_field_definitions = $config->get('field_definitions');
    foreach ($role_schema_types as $role_schema_id => $role_field_names) {
      [$role_schema_type, $role_schema_property] = explode('--', $role_schema_id);
      if (!$this->schemaTypeManager->isSubTypeOf($schema_type, $role_schema_type)) {
        continue;
      }

      foreach ($role_field_names as $role_field_name) {
        if (isset($role_field_definitions[$role_field_name])
          && !isset($field_definitions[$role_field_name])) {
          $field_definitions[$role_field_name] = [
            'schema_type' => $schema_type,
            'schema_property' => $role_schema_property,
          ] + $role_field_definitions[$role_field_name];
        }
      }
    }

    return $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingFieldDefinitions(SchemaDotOrgMappingInterface $mapping): array {
    return $this->getSchemaTypeFieldDefinitions($mapping->getSchemaType());
  }

}
