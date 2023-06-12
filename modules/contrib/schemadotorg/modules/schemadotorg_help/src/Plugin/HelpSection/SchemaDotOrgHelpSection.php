<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_help\Plugin\HelpSection;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\help\Plugin\HelpSection\HelpSectionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Schema.org Blueprints section for the help page.
 *
 * @HelpSection(
 *   id = "schemadotorg",
 *   title = @Translation("Schema.org Blueprints"),
 *   weight = 20,
 *   description = @Translation("The Schema.org Blueprints module uses Schema.org as the blueprint for the content architecture and structured data in a Drupal website."),
 *   permission = "access administration pages"
 * )
 */
class SchemaDotOrgHelpSection extends HelpSectionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TourHelpSection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   */
  public function __construct(array $configuration, string $plugin_id, mixed $plugin_definition, ModuleExtensionList $module_extension_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function listTopics() {
    $modules = array_filter($this->moduleExtensionList->getAllInstalledInfo(), function (array $info): bool {
      return str_starts_with($info['package'], 'Schema.org Blueprints');
    });
    ksort($modules);

    $topics = [];
    foreach ($modules as $module_name => $module_info) {
      $title = $module_info['name'];
      $url = Url::fromRoute('schemadotorg_help.page', ['name' => $module_name]);
      $topics[$module_name] = Link::fromTextAndUrl($title, $url)->toString();
    }
    return $topics;
  }

}
