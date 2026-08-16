<?php

declare(strict_types=1);

namespace Drupal\editoria11y_csa;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of editoria11y custom tests.
 */
final class Ed11yCustomRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['test'] = $this->t('Test');
    $header['element_set'] = $this->t('Elements');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\editoria11y_csa\Ed11yCustomRuleInterface $entity */
    $elements = $entity->get('element_set');
    if ($elements === 'Paragraphs,Headings,Lists,Blockquotes') {
      $elements = $this->t('Text');
    }
    $row['test'] = $entity->label();
    $row['element_set'] = $elements;
    $row['status'] = $entity->status() ? $this->t('On') : $this->t('Off');
    return $row + parent::buildRow($entity);
  }

}
