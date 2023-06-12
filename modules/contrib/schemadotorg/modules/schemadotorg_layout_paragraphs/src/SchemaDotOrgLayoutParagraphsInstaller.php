<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_layout_paragraphs;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemadotorg\SchemaDotOrgEntityFieldManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface;
use Drupal\schemadotorg\SchemaDotOrgNamesInterface;

/**
 * Schema.org layout paragraphs installer.
 */
class SchemaDotOrgLayoutParagraphsInstaller implements SchemaDotOrgLayoutParagraphsInstallerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgLayoutParagraphsInstaller object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   The entity display repository.
   * @param \Drupal\schemadotorg\SchemaDotOrgNamesInterface $schemaNames
   *   The Schema.org names service.
   * @param \Drupal\schemadotorg\SchemaDotOrgMappingManagerInterface $schemaMappingManager
   *   The Schema.org mapping manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityDisplayRepositoryInterface $entityDisplayRepository,
    protected SchemaDotOrgNamesInterface $schemaNames,
    protected SchemaDotOrgMappingManagerInterface $schemaMappingManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function install(): void {
    // Setting weight to 1 so that the Schema.org Layout Paragraphs hooks
    // are triggered after the Schema.org Paragraphs module.
    module_set_weight('schemadotorg_layout_paragraphs', 1);

    $this->createMediaParagraphTypes();
    $this->createDefaultParagraphTypes();
    $this->updateNodeParagraph();
  }

  /**
   * Create media paragraph types.
   */
  protected function createMediaParagraphTypes(): void {
    /** @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = [
      'audio' => [
        'label' => $this->t('Audio'),
        'schema_type' => 'AudioObject',
      ],
      'image' => [
        'label' => $this->t('Image'),
        'schema_type' => 'ImageObject',
      ],
      'remote_video' => [
        'label' => $this->t('Video'),
        'schema_type' => 'VideoObject',
      ],
    ];
    foreach ($media_types as $media_type_id => $media_type_info) {
      $media_type = $this->entityTypeManager
        ->getStorage('media_type')
        ->load($media_type_id);

      // Make sure the media type exists.
      if (!$media_type) {
        continue;
      }

      $paragraph_type = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->load($media_type_id);

      // Make sure the paragraph type does not exist.
      if ($paragraph_type) {
        continue;
      }

      $label = $media_type_info['label'];

      // Create a paragraph type for media.
      // (i.e. paragraph_type:image => media_type:image)
      $schema_type = $media_type_info['schema_type'];
      $defaults = $this->schemaMappingManager->getMappingDefaults('paragraph', NULL, $schema_type);
      $defaults['entity']['label'] = $label;
      $defaults['entity']['id'] = $media_type_id;
      // Use the mainEntityOfPage to store a reference to a media entity.
      $main_entity_of_page = $defaults['properties']['mainEntityOfPage'];
      $main_entity_of_page['name'] = '_add_';
      $main_entity_of_page['type'] = 'field_ui:entity_reference:media';
      $defaults['properties'] = ['mainEntityOfPage' => $main_entity_of_page];
      $this->schemaMappingManager->saveMapping('paragraph', $schema_type, $defaults);

      $field_name = $this->schemaNames->getFieldPrefix() . $main_entity_of_page['machine_name'];

      /** @var \Drupal\field\FieldConfigInterface $field */
      $field = $this->entityTypeManager
        ->getStorage('field_config')
        ->load("paragraph.$media_type_id.$field_name");
      // Change the field label from 'Main entity of page' to the media type.
      $field->set('label', $label);
      // Update the field settings to target only the corresponding media type.
      $settings = $field->getSettings();
      $settings['handler_settings']['target_bundles'] = [$media_type_id => $media_type_id];
      $field->setSettings($settings);
      $field->save();

      // Hide the field label.
      $display = $this->entityDisplayRepository->getViewDisplay('paragraph', $media_type_id);
      $component = $display->getComponent($field_name);
      $component['label'] = 'hidden';
      $display->setComponent($field_name, $component);
      $display->save();
    }
  }

  /**
   * Create default paragraph types.
   */
  protected function createDefaultParagraphTypes(): void {
    $schema_types = [
      'quotation' => 'Quotation',
      'item_list_text' => 'ItemList',
      'item_list_string' => 'ItemList',
      'item_list_link' => 'ItemList',
      'statement' => 'Statement',
      'header' => 'Statement',
      'collection_page' => 'CollectionPage',
      'media_gallery' => 'MediaGallery',
      'image_gallery' => 'ImageGallery',
      'video_gallery' => 'VideoGallery',
    ];
    foreach ($schema_types as $paragraph_type_id => $schema_type) {
      $paragraph_type = $this->entityTypeManager
        ->getStorage('paragraphs_type')
        ->load($paragraph_type_id);

      // Make sure the paragraph type does not exist.
      if ($paragraph_type) {
        continue;
      }

      switch ($paragraph_type_id) {
        case 'collection_page':
        case 'media_gallery':
        case 'image_gallery':
        case 'video_gallery':
          // For galleries, we only want to support media selection via hasPart.
          $defaults = [
            'properties' => [
              'hasPart' => [
                'type' => 'field_ui:entity_reference_revisions:paragraph',
              ],
              'name' => FALSE,
              'text' => FALSE,
            ],
          ];
          break;

        case 'item_list_text':
          $defaults = [
            'entity' => [
              'id' => $paragraph_type_id,
              'label' => (string) $this->t('List (Rich text)'),
              'description' => (string) $this->t('A list of rich text items.'),
            ],
            'properties' => [
              'itemListElement' => [
                'machine_name' => 'item_list_element_text',
                'type' => 'text_long',
              ],
            ],
          ];
          break;

        case 'item_list_string':
          $defaults = [
            'entity' => [
              'id' => $paragraph_type_id,
              'label' => (string) $this->t('List (Plain text)'),
              'description' => (string) $this->t('A list of plain text items.'),
            ],
            'properties' => [
              'itemListElement' => [
                'machine_name' => 'item_list_element_string',
                'type' => 'string',
              ],
            ],
          ];
          break;

        case 'item_list_link':
          $defaults = [
            'entity' => [
              'id' => $paragraph_type_id,
              'label' => (string) $this->t('List (Links)'),
              'description' => (string) $this->t('A list of links.'),
            ],
            'properties' => [
              'itemListElement' => [
                'machine_name' => 'item_list_element_link',
                'type' => 'link',
              ],
            ],
          ];
          break;

        case 'header':
          // For header, we only want the name/title.
          $defaults = [
            'entity' => [
              'id' => 'header',
              'label' => (string) $this->t('Header'),
              'description' => (string) $this->t('A heading.'),
            ],
            'properties' => [
              'text' => [
                'type' => 'string',
                'name' => SchemaDotOrgEntityFieldManagerInterface::ADD_FIELD,
                'label' => (string) $this->t('Header text'),
                'description' => (string) $this->t('A heading to be displayed on the page.'),
                'machine_name' => 'header_text',
              ],
            ],
          ];
          break;

        default:
          $defaults = [];
          break;
      }

      // Create the paragraph type and Schema.org mapping.
      $this->schemaMappingManager->createType('paragraph', $schema_type, $defaults);

      // Hide all component labels for schema_* fields.
      $display = $this->entityDisplayRepository->getViewDisplay('paragraph', $paragraph_type_id);
      $components = $display->getComponents();
      foreach ($components as $field_name => $component) {
        if (str_starts_with($field_name, 'schema_')) {
          $component['label'] = 'hidden';
          $display->setComponent($field_name, $component);
        }
      }
      $display->save();
    }
  }

  /**
   * Update node paragraph target bundles and form display.
   */
  protected function updateNodeParagraph(): void {
    // Update node paragraph target bundles.
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->getQuery()
      ->execute();

    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_config = $this->entityTypeManager
      ->getStorage('field_config')
      ->load("paragraph.node.field_node");
    $settings = $field_config->get('settings');
    $settings['handler_settings']['target_bundles'] = $node_types;
    $field_config->set('settings', $settings);
    $field_config->save();

    // Update node paragraph form display to use the content browser.
    if ($this->moduleHandler->moduleExists('content_browser')) {
      $form_display = $this->entityDisplayRepository->getFormDisplay('paragraph', 'node');
      $form_component = $form_display->getComponent('field_node');
      $form_component['type'] = 'entity_browser_entity_reference';
      $form_component['settings'] = [
        'entity_browser' => 'browse_content',
        'field_widget_display' => 'label',
        'field_widget_edit' => TRUE,
        'field_widget_remove' => TRUE,
        'field_widget_replace' => TRUE,
        'open' => TRUE,
        'field_widget_display_settings' => [],
        'selection_mode' => 'selection_append',
      ];
      $form_display->setComponent('field_node', $form_component);
      $form_display->save();
    }
  }

}
