<?php

/**
 * @file
 * Token callbacks for the fivestar module.
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function fivestar_token_info_alter(&$info) {
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $fivestar_field_map = $entity_field_manager->getFieldMapByFieldType('fivestar');

  foreach (array_keys($fivestar_field_map) as $entity_type) {
    if (!array_key_exists($entity_type, $info['tokens'])) {
      continue;
    }

    $info['tokens'][$entity_type]['fivestar-rating'] = [
      'name' => t('Fivestar rating'),
    ];
    $info['tokens'][$entity_type]['fivestar-count'] = [
      'name' => t('Fivestar vote sum'),
    ];
  }
}

/**
 * Implements hook_tokens().
 */
function fivestar_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if (
    !array_key_exists('fivestar-rating', $tokens) &&
    !array_key_exists('fivestar-count', $tokens)
  ) {
    return [];
  }

  if (
    !array_key_exists($type, $data) ||
    !$data[$type] instanceof FieldableEntityInterface
  ) {
    return [];
  }

  $replacements = [];
  $results = \Drupal::service('fivestar.vote_result_manager')->getResults($data[$type]);
  foreach ([
    'fivestar-rating' => 'vote_average',
    'fivestar-count' => 'vote_count',
  ] as $token => $result_property) {
    if (!array_key_exists($token, $tokens)) {
      continue;
    }
    if (
      !array_key_exists('vote', $results) ||
      !array_key_exists($result_property, $results['vote'])
    ) {
      $value = '0';
    }
    else {
      $value = $results['vote'][$result_property];
    }
    $replacements[$tokens[$token]] = $value;
  }

  return $replacements;
}
