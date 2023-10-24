<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides derivative plugins for the DefaultSelection plugin.
 *
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 */
class SchemaDotOrgEntitySelectionDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creates a DefaultSelectionDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver::getDerivativeDefinitions
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $entity_type_label = $entity_type->getLabel() ?? $entity_type_id;

      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['entity_types'] = [$entity_type_id];
      $this->derivatives[$entity_type_id]['label'] = $this->t('@entity_type selection', ['@entity_type' => $entity_type_label]);
      $this->derivatives[$entity_type_id]['base_plugin_label'] = (string) $base_plugin_definition['label'];
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
