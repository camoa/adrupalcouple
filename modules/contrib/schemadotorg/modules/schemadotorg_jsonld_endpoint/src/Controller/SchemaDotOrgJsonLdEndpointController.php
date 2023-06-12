<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_endpoint\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Schema.org JSON-LD endpoint routes.
 */
class SchemaDotOrgJsonLdEndpointController extends ControllerBase {

  /**
   * The Schema.org JSON-LD manager.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdManagerInterface
   */
  protected $manager;

  /**
   * The Schema.org JSON-LD builder.
   *
   * @var \Drupal\schemadotorg_jsonld\SchemaDotOrgJsonLdBuilderInterface
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->manager = $container->get('schemadotorg_jsonld.manager');
    $instance->builder = $container->get('schemadotorg_jsonld.builder');
    return $instance;
  }

  /**
   * Build the Schema.org JSON-LD response for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Schema.org JSON-LD response for an entity.
   */
  public function getEntity(EntityInterface $entity): JsonResponse {
    $entity_route_match = $this->manager->getEntityRouteMatch($entity);
    if ($entity_route_match) {
      $data = $this->builder->build($entity_route_match);
    }
    else {
      $data = $this->builder->buildEntity($entity);
      if ($data) {
        $data = ['@context' => 'https://schema.org'] + $data;
      }
    }

    if (!$data) {
      throw new NotFoundHttpException();
    }

    return new JsonResponse($data);
  }

  /**
   * Checks view access to an entity's Schema.org JSON-LD.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, EntityInterface $entity): AccessResultInterface {
    return $entity->access('view', $account, TRUE);
  }

}
