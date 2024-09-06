<?php

namespace Drupal\Tests\dropdown_language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * General testing for the dropdown_language module.
 */
abstract class DropdownLanguageTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'language_test',
    'test_page_test',
    'language',
    'block',
    'block_content',
    'content_translation',
    'locale',
    'dropdown_language',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The available languages.
   *
   * @var array
   */
  protected $languages;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);

    // Create a translatable content type:
    $this->createContentType(['type' => 'article', 'translatable' => TRUE]);

    // Add the German language:
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->languages = $this->container->get('language_manager')->getLanguages();

    // Enable the path prefixes for the given languages:
    $this->config('language.negotiation')->set('url.prefixes.en', 'en')->save();
    $this->config('language.negotiation')->set('url.prefixes.de', 'de')->save();
  }

}
