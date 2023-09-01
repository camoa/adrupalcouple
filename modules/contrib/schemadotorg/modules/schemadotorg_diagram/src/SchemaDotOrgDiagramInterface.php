<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_diagram;

use Drupal\node\NodeInterface;

/**
 * Schema.org diagram interface.
 */
interface SchemaDotOrgDiagramInterface {

  /**
   * Build a diagram.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param string|null $parent_property
   *   The parent Schema.org property.
   * @param string|null $child_property
   *   The child Schema.org property.
   * @param string|null $title
   *   The diagram's title.
   *
   * @return array|null
   *   The diagram.
   */
  public function build(NodeInterface $node, ?string $parent_property, ?string $child_property, ?string $title): ?array;

}
