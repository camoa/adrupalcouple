<?php

namespace Drupal\fivestar;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Contains methods for managing vote results.
 */
interface VoteResultManagerInterface {

  /**
   * Gets votes for an entity based on vote type.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that was voted on.
   * @param string $vote_type
   *   The vote type.
   *
   * @return array
   *   An array of votes on the entity for the given vote type..
   */
  public function getResultsByVoteType(FieldableEntityInterface $entity, string $vote_type): array;

  /**
   * Gets all votes for an entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that was voted on.
   *
   * @return array
   *   An array of all votes on the entity.
   */
  public function getResults(FieldableEntityInterface $entity): array;

  /**
   * Returns default result collection.
   *
   * @return array
   *   An associative array with keys:
   *   - vote_sum: The sum of all votes.
   *   - vote_user: The user's vote.
   *   - vote_count: The number of votes.
   *   - vote_average: The average of all votes.
   */
  public function getDefaultResults(): array;

  /**
   * Recalculates votes results.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that was voted on.
   */
  public function recalculateResults(FieldableEntityInterface $entity): void;

}
