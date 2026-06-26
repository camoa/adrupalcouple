<?php

declare(strict_types=1);

namespace Drupal\adrupalcouple_chrome\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the ADrupalCouple SDC footer block.
 *
 * Loads the footer_writing and footer_about menus and passes rendered link
 * items into the adrupalcouple:footer SDC slots via an inline_template render
 * element. All business logic lives here in PHP; the SDC template
 * (footer.twig) stays free of menu loading — per the chrome task spec.
 *
 * @Block(
 *   id = "adrupalcouple_sdc_footer",
 *   admin_label = @Translation("ADrupalCouple SDC Footer"),
 *   category = @Translation("ADrupalCouple"),
 * )
 */
final class SdcFooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly MenuLinkTreeInterface $menuLinkTree,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Renders the adrupalcouple:footer SDC via an inline_template, passing the
   * writing_links and about_links slots as render arrays of #type: link items.
   * The SDC Twig template outputs {{ writing_links }} / {{ about_links }} and
   * Drupal's Twig extension auto-renders the render arrays.
   */
  public function build(): array {
    $site_name = (string) ($this->configFactory->get('system.site')->get('name') ?? 'A Drupal Couple');
    $writing_links = $this->buildMenuLinks('footer-writing');
    $about_links = $this->buildMenuLinks('footer-about');

    // Use inline_template so the adrupalcouple:footer SDC include works without
    // requiring the sdc module's #type: component render element.
    return [
      '#type' => 'inline_template',
      // phpcs:ignore
      '#template' => "{{ include('adrupalcouple:footer', {brand_name: brand_name, rights_text: rights_text, built_note: built_note, writing_nav_label: writing_nav_label, about_nav_label: about_nav_label, writing_links: writing_links, about_links: about_links}, with_context: false) }}",
      '#context' => [
        'brand_name' => $site_name,
        'rights_text' => $this->t('Written by two people, in English and Spanish.'),
        'built_note' => $this->t('Built with Drupal, read on quiet pages.'),
        'writing_nav_label' => $this->t('Writing'),
        'about_nav_label' => $this->t('The couple'),
        'writing_links' => $writing_links,
        'about_links' => $about_links,
      ],
    ];
  }

  /**
   * Loads a menu tree (depth 1) and returns renderable #type:link items.
   *
   * Each item carries classes 'link link-hover' as required by the footer SDC
   * slot spec (footer.component.yml: "Each <a> must carry classes link
   * link-hover").
   *
   * @param string $menu_name
   *   The menu machine name to load.
   *
   * @return array
   *   A render array of #type: link children, keyed by plugin ID.
   *   Empty array when the menu has no accessible links.
   */
  private function buildMenuLinks(string $menu_name): array {
    $params = new MenuTreeParameters();
    $params->setMaxDepth(1);

    $tree = $this->menuLinkTree->load($menu_name, $params);
    $tree = $this->menuLinkTree->transform($tree, [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ]);

    $items = [];
    foreach ($tree as $element) {
      // After checkAccess: NULL means unchecked (allow); otherwise check result.
      if ($element->access !== NULL && !$element->access->isAllowed()) {
        continue;
      }
      $link = $element->link;
      $items[$link->getPluginId()] = [
        '#type' => 'link',
        '#title' => $link->getTitle(),
        '#url' => $link->getUrlObject(),
        '#attributes' => ['class' => ['link', 'link-hover']],
      ];
    }
    return $items;
  }

}
