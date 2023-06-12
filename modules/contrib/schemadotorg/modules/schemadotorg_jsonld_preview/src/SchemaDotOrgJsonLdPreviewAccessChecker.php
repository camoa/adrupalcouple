<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_jsonld_preview;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountInterface;

/**
 * Schema.org JSON-LD preview access checker.
 */
class SchemaDotOrgJsonLdPreviewAccessChecker implements SchemaDotOrgJsonLdPreviewAccessCheckerInterface {

  /**
   * Constructs a SchemaDotOrgJsonLdPreviewAccessChecker object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The route admin context to determine whether the route is an admin one.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The ConditionManager for building the visibility UI.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
    protected AdminContext $adminContext,
    protected ConditionManager $conditionManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function access(): bool {
    // Check current route.
    if ($this->adminContext->isAdminRoute()) {
      return FALSE;
    }

    // Check that the current user can view the Schema.org JSON-LD.
    if (!$this->currentUser->hasPermission('view schemadotorg jsonld')) {
      return FALSE;
    }

    $config = $this->configFactory->get('schemadotorg_jsonld_preview.settings');

    // Load the request path condition plugin.
    /** @var \Drupal\system\Plugin\Condition\RequestPath $condition */
    $condition = $this->conditionManager->createInstance('request_path');

    // Set the visibility request path condition configuration.
    $condition->setConfiguration($config->get('visibility.request_path'));

    // Execute the request path condition.
    return $condition->execute();
  }

}
