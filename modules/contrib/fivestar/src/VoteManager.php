<?php

namespace Drupal\fivestar;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\votingapi\VoteInterface;

/**
 * Contains methods for managing votes.
 */
class VoteManager implements VoteManagerInterface {

  /**
   * The vote storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $voteStorage;

  /**
   * Constructs a new VoteManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {
    $this->voteStorage = $entityTypeManager->getStorage('vote');
  }

  /**
   * {@inheritdoc}
   */
  public function getVoteTypes(): array {
    $options = [];
    $vote_type_storage = $this->entityTypeManager->getStorage('vote_type');

    foreach ($vote_type_storage->loadMultiple() as $vote_type) {
      $options[$vote_type->id()] = $vote_type->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function addVote(FieldableEntityInterface $entity, mixed $rating, string $vote_type = 'vote', $uid = NULL): VoteInterface {
    $uid = is_numeric($uid) ? $uid : $this->currentUser->id();
    $rating = ($rating > 100) ? 100 : $rating;

    $vote = $this->voteStorage->create(['type' => $vote_type]);
    $vote->setVotedEntityId($entity->id());
    $vote->setVotedEntityType($entity->getEntityTypeId());
    $vote->setOwnerId($uid);
    $vote->setValue($rating);
    $vote->save();

    return $vote;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteVote(): void {
  }

  /**
   * {@inheritdoc}
   */
  public function getVotesByCriteria(array $criteria): array {
    if (empty($criteria)) {
      return [];
    }

    return $this->voteStorage->loadByProperties($criteria);
  }

}
