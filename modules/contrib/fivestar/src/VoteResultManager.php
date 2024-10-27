<?php

namespace Drupal\fivestar;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\votingapi\VoteResultFunctionManagerInterface;

/**
 * Contains methods for managing vote results.
 */
class VoteResultManager implements VoteResultManagerInterface {

  /**
   * Constructs a new VoteResultManager object.
   *
   * @param \Drupal\votingapi\VoteResultFunctionManagerInterface $voteResultManager
   *   The vote result manager.
   */
  public function __construct(
    protected VoteResultFunctionManagerInterface $voteResultManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getResultsByVoteType(FieldableEntityInterface $entity, string $vote_type): array {
    $results = $this->getResults($entity);
    if (isset($results[$vote_type])) {
      return $results[$vote_type];
    }

    return $this->getDefaultResults();
  }

  /**
   * {@inheritdoc}
   */
  public function getResults(FieldableEntityInterface $entity): array {
    $results = $this->voteResultManager->getResults(
      $entity->getEntityTypeId(),
      $entity->id()
    );

    return !empty($results) ? $results : $this->getDefaultResults();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultResults(): array {
    return [
      'vote_sum' => 0,
      'vote_user' => 0,
      'vote_count' => 0,
      'vote_average' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function recalculateResults(FieldableEntityInterface $entity): void {
    $this->voteResultManager->recalculateResults(
      $entity->getEntityTypeId(),
      $entity->id(),
      $entity->bundle()
    );
  }

}
