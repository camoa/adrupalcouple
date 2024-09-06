<?php

namespace Drupal\Tests\dropdown_language\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests for the dropdown_language content language block.
 *
 * @group dropdown_language
 */
class ContentLanguageTest extends DropdownLanguageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make both language_interface and language_content configurable:
    $this->config('language.types')->set('configurable', [
      'language_interface',
      'language_content',
    ])->save();
    // Enable URL language detection:
    $edit = [
      'language_content[enabled][language-url]' => TRUE,
      'language_content[weight][language-url]' => -10,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');
    $this->drupalGet('admin/config/regional/language/detection');
    // Create a dropdown language interface block:
    $this->placeBlock('dropdown_language:' . LanguageInterface::TYPE_CONTENT, [
      'region' => 'content',
      'id' => 'test_language_content_block',
    ]);
  }

  /**
   * See if the interface block exists on the front page.
   */
  public function testblockExists() {
    $session = $this->assertSession();

    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Test page text.');
    // Check if the block exists and contains the correct properties:
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementExists('css', '#block-test-language-content-block div.dropbutton-widget');
    $session->elementTextEquals('css', '#block-test-language-content-block span.language-link.active-language', 'English');
    $session->elementTextEquals('css', '#block-test-language-content-block a.language-link', 'German');
  }

  /**
   * See if the block doesn't render, when there is only one Language.
   */
  public function testBlockLanguageRemoved() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Test page text.');
    // Check if the block exists and contains the correct properties:
    $session->elementExists('css', '#block-test-language-content-block');

    // Remove the german language:
    $this->drupalGet('/admin/config/regional/language/delete/de');
    $page->pressButton('Delete');

    // Go to front page and see if the block is gone:
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Test page text.');
    // Check if the block exists and contains the correct properties:
    $session->elementNotExists('css', '#block-test-language-content-block');
  }

  /**
   * Check if the block exists on a non-node page.
   */
  public function testBlockExistsOnSystemPage() {
    $session = $this->assertSession();

    $this->drupalGet('/user/login');
    $session->statusCodeEquals(200);
    // Check if the block exists and contains the correct properties:
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementExists('css', '#block-test-language-content-block div.dropbutton-widget');
    $session->elementTextEquals('css', '#block-test-language-content-block span.language-link.active-language', 'English');
    $session->elementTextEquals('css', '#block-test-language-content-block a.language-link', 'German');
  }

  /**
   * Check if the block doesn't exist on a not existing page.
   */
  public function testBlockNotExistsOnNonExistant() {
    $session = $this->assertSession();

    $this->drupalGet('/non-existant page');
    $session->statusCodeEquals(404);
    $session->elementNotExists('css', '#block-test-language-content-block');
  }

  /**
   * Check if the block doesn't exist.
   *
   * Check if the block doesn't exist for anonymous user on a not accessible
   * admin page.
   */
  public function testBlockNotExistOnAdminAsAnon() {
    $session = $this->assertSession();

    $this->drupalLogout();
    $this->drupalGet('/admin');
    $session->statusCodeEquals(403);
    // Check if the block not exists:
    $session->elementNotExists('css', '#block-test-language-content-block');
  }

  /**
   * Test block works.
   *
   * Tests if the block appears on nodes translated to the current language and
   * that it contain a link to the translation.
   */
  public function testBlockWorks() {
    $session = $this->assertSession();
    // Create node and translation:
    $node = $this->createNode([
      'type' => 'article',
      'id' => 1,
      'body' => 'English body content',
      'title' => 'English Version',
    ]);
    $node->addTranslation('de', [
      'body' => 'German body content',
      'title' => 'German Version',
    ])->save();
    // Check if the block exists on a translated node:
    $this->drupalGet('/de/node/1');
    $session->pageTextContains('German body content');
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementTextContains('css', '#block-test-language-content-block span.language-link.active-language', 'German');
    // Check if there is a link to the english version:
    $session->elementTextEquals('css', '#block-test-language-content-block ul > li > a', 'English');
    $session->elementAttributeContains('css', '#block-test-language-content-block ul > li > a', 'href', '/en/node/1');

    // Check if the block exists on the original node:
    $this->drupalGet('/node/1');
    $session->pageTextContains('English body content');
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementTextContains('css', '#block-test-language-content-block span.language-link.active-language', 'English');
    // Check if there is a link to the german version:
    $session->elementTextEquals('css', '#block-test-language-content-block ul > li > a', 'German');
    $session->elementAttributeContains('css', '#block-test-language-content-block ul > li > a', 'href', '/de/node/1');

  }

  /**
   * Tests if the block appears on a not translated node.
   */
  public function testBlockExistsOnNonTranslatedPage() {
    $session = $this->assertSession();
    // Create a node, but don't create a translation:
    $this->createNode([
      'type' => 'article',
      'id' => 1,
      'body' => 'Body content',
      'title' => 'English Version',
    ]);

    // Change the default language:
    $this->drupalGet('admin/config/regional/language');

    // Change the default language to german:
    $edit = [
      'site_default_language' => 'de',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->rebuildContainer();

    // Check if the block exists on the german version, which is not translated:
    $this->drupalGet('/node/1');
    $session->pageTextContains('Body content');
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementTextContains('css', '#block-test-language-content-block span.language-link.active-language', 'German');
  }

  /**
   * See if multiple languages are visible in the dropdown menu.
   */
  public function testMultipleLanguagesShown() {
    $session = $this->assertSession();

    // Create two more languages:
    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Test page text.');
    // Check if the block exists and contains the correct properties:
    $session->elementExists('css', '#block-test-language-content-block');
    $session->elementExists('css', '#block-test-language-content-block div.dropbutton-widget');
    $session->elementTextEquals('css', '#block-test-language-content-block span.language-link.active-language', 'English');
    $session->elementTextEquals('css', '#block-test-language-content-block ul.dropdown-language-item > li:nth-child(2) > a', 'French');
    $session->elementTextEquals('css', '#block-test-language-content-block ul.dropdown-language-item > li:nth-child(3) > a', 'German');
    $session->elementTextEquals('css', '#block-test-language-content-block ul.dropdown-language-item > li:nth-child(4) > a', 'Italian');
  }

}
