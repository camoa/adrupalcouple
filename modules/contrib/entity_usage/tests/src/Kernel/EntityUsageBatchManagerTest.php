<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_usage\Kernel;

use Drupal\entity_usage\EntityUsageBatchManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests \Drupal\entity_usage\EntityUsageBatchManager.
 *
 * @group entity_usage
 *
 * @package Drupal\Tests\entity_usage\Kernel
 */
#[Group('entity_usage')]
#[RunTestsInSeparateProcesses]
class EntityUsageBatchManagerTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_usage'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('entity_usage', ['entity_usage']);
    $this->installConfig('entity_usage');
  }

  /**
   * Tests creating new data while a bulk recreate is in progress.
   *
   * @covers \Drupal\entity_usage\EntityUsageBatchManager::copyBulkTable
   * @covers \Drupal\entity_usage\EntityUsageBatchManager::restoreNewData
   */
  public function testRestoreNewData(): void {
    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');

    $context = [];
    // Create bulk and backup tables.
    EntityUsageBatchManager::createBulkTable($context);

    // Simulate bulk recreate.
    $entity_usage->enableBulkInsert(EntityUsageBatchManager::BULK_TABLE_NAME);
    $entity_usage->registerUsage(11, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body', 2);
    $entity_usage->registerUsage(12, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->registerUsage(13, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->registerUsage(17, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->bulkInsert();

    // Simulate usage data being creating while the recreate is in progress.
    $entity_usage->enableBulkInsert('entity_usage');
    // The next usage being inserted is also in the bulk table. This could occur
    // if a user saves a node without creating a new revision.
    $entity_usage->registerUsage(13, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->registerUsage(14, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->registerUsage(15, 'test', 1, 'foo', 'en', 1, 'entity_reference', 'body');
    $entity_usage->bulkInsert();

    $this->assertSame(3, (int) $database->select('entity_usage')->countQuery()->execute()->fetchField());
    $this->assertSame(4, (int) $database->select(EntityUsageBatchManager::BULK_TABLE_NAME)->countQuery()->execute()->fetchField());
    $this->assertSame(0, (int) $database->select(EntityUsageBatchManager::BACKUP_TABLE_NAME)->countQuery()->execute()->fetchField());

    EntityUsageBatchManager::copyBulkTable($context);
    $this->assertSame(4, (int) $database->select('entity_usage')->countQuery()->execute()->fetchField());
    $this->assertSame(4, (int) $database->select(EntityUsageBatchManager::BULK_TABLE_NAME)->countQuery()->execute()->fetchField());
    $this->assertSame(3, (int) $database->select(EntityUsageBatchManager::BACKUP_TABLE_NAME)->countQuery()->execute()->fetchField());

    EntityUsageBatchManager::restoreNewData($context);
    $this->assertSame(6, (int) $database->select('entity_usage')->countQuery()->execute()->fetchField());
    $this->assertSame(4, (int) $database->select(EntityUsageBatchManager::BULK_TABLE_NAME)->countQuery()->execute()->fetchField());
    $this->assertSame(3, (int) $database->select(EntityUsageBatchManager::BACKUP_TABLE_NAME)->countQuery()->execute()->fetchField());

    $data = array_keys($database->select('entity_usage')->fields('entity_usage', ['target_id'])->orderby('target_id')->execute()->fetchAllAssoc('target_id'));
    $this->assertSame([11, 12, 13, 14, 15, 17], $data);
  }

}
