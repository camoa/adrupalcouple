<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_ui\Functional;

use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\schemadotorg\Functional\SchemaDotOrgBrowserTestBase;

/**
 * Tests the functionality of the Schema.org UI controller.
 *
 * @covers \Drupal\schemadotorg_ui\Controller\SchemaDotOrgUiMappingController
 * @group schemadotorg
 */
class SchemaDotOrgUiControllerTest extends SchemaDotOrgBrowserTestBase {
  use MediaTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'media',
    'paragraphs',
    'field',
    'field_ui',
    'file',
    'datetime',
    'image',
    'telephone',
    'link',
    'options',
    'schemadotorg_media',
    'schemadotorg_paragraphs',
    'schemadotorg_ui',
  ];

  /**
   * Test SSchema.org UI controller.
   */
  public function testController(): void {
    $assert_session = $this->assertSession();

    // Check that access is denied to 'Add Schema.org type' page.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/search/schemadotorg/add');
    $assert_session->statusCodeEquals(403);

    // Check that access is allowed to 'Add Schema.org type' page.
    $account = $this->drupalCreateUser([
      'administer user fields',
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer schemadotorg',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/search/schemadotorg/add');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Content type');
    $assert_session->linkExists('Paragraphs type');
    $assert_session->linkExists('User (Person)');

    // Check that access is allowed to 'Add Schema.org type' page.
    $account = $this->drupalCreateUser(['administer schemadotorg']);
    $this->drupalLogin($account);

    // Check that access is allowed to 'Add Schema.org type' page but
    // denied to entity type pages.
    $this->drupalGet('/admin/config/search/schemadotorg/add');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Content type');
    $assert_session->linkNotExists('Paragraphs type');
    $assert_session->linkNotExists('User (Person)');
  }

}
