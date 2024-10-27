<?php

namespace Drupal\fivestar;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\votingapi\VoteInterface;

/**
 * Contains methods for managing votes.
 */
interface VoteManagerInterface {

  /**
   * Gets vote types.
   *
   * @return array
   *   An associative array with keys equal to the vote type machine ID and
   *   values equal to the vote type human-readable label.
   */
  public function getVoteTypes(): array;

  /**
   * Adds a vote.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to which the vote is attached.
   * @param mixed $rating
   *   The value of the vote.
   * @param string $vote_type
   *   Vote type.
   * @param int|null $uid
   *   ID of the user who voted.
   *
   * @return \Drupal\votingapi\VoteInterface
   *   The added Vote.
   */
  public function addVote(FieldableEntityInterface $entity, mixed $rating, string $vote_type = 'vote', $uid = NULL): VoteInterface;

  /**
   * Deletes a vote.
   */
  public function deleteVote(): void;

  /**
   * Gets votes by criteria.
   *
   * @param array $criteria
   *   Associative array of criteria. Keys are:
   *   - entity_id: The entity id.
   *   - entity_type: The entity type.
   *   - type: Vote type.
   *   - user_id: The user id.
   *   - vote_source: The vote source.
   *
   * @return array
   *   Which contain vote ids.
   */
  public function getVotesByCriteria(array $criteria): array;

}
