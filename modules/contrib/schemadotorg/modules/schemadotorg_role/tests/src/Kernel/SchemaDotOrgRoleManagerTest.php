<?php

declare(strict_types = 1);

namespace Drupal\Tests\schemadotorg_role\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\schemadotorg\Entity\SchemaDotOrgMapping;
use Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase;

/**
 * Tests the functionality of the Schema.org role field.
 *
 * @covers \Drupal\schemadotorg_role\SchemaDotOrgRoleManager
 * @group schemadotorg
 */
class SchemaDotOrgRoleManagerTest extends SchemaDotOrgKernelEntityTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'field_group',
    'schemadotorg_field_group',
    'schemadotorg_role',
  ];

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The Schema.org role manager.
   *
   * @var \Drupal\schemadotorg_role\SchemaDotOrgRoleManagerInterface
   */
  protected $roleManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'schemadotorg_field_group',
      'schemadotorg_role',
    ]);

    $this->entityDisplayRepository = $this->container->get('entity_display.repository');

    $this->roleManager = $this->container->get('schemadotorg_role.manager');
  }

  /**
   * Test Schema.org role.
   */
  public function testRole(): void {
    $this->createSchemaEntity('node', 'PodcastEpisode');

    /* ********************************************************************** */

    // Check that the guest and host role fields are created when
    // a mapping is inserted.
    $this->assertNull(FieldConfig::loadByName('node', 'podcast_episode', 'schema_actor'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'podcast_episode', 'schema_role_guest'));
    $this->assertNotNull(FieldConfig::loadByName('node', 'podcast_episode', 'schema_role_host'));

    // Check that the guest and host role is created via the form display.
    $form_display = $this->entityDisplayRepository->getFormDisplay('node', 'podcast_episode', 'default');
    $component = $form_display->getComponent('schema_role_guest');
    $this->assertEquals('string_textfield', $component['type']);
    $component = $form_display->getComponent('schema_role_host');
    $this->assertEquals('string_textfield', $component['type']);
    $field_group = $form_display->getThirdPartySettings('field_group');
    $this->assertEquals(
      ['schema_date_published', 'schema_episode_number', 'schema_role_host', 'schema_role_guest'],
      $field_group['group_podcast_episode']['children']
    );
    $this->assertEquals('Podcast episode', $field_group['group_podcast_episode']['label']);
    $this->assertEquals('details', $field_group['group_podcast_episode']['format_type']);

    // Check that the guest and host role is created via the view display.
    $view_display = $this->entityDisplayRepository->getViewDisplay('node', 'podcast_episode', 'default');
    $component = $view_display->getComponent('schema_role_guest');
    $this->assertEquals('string', $component['type']);
    $component = $view_display->getComponent('schema_role_host');
    $this->assertEquals('string', $component['type']);
    $field_group = $view_display->getThirdPartySettings('field_group');
    $this->assertEquals(
      ['schema_date_published', 'schema_episode_number', 'schema_role_host', 'schema_role_guest'],
      $field_group['group_podcast_episode']['children']
    );
    $this->assertEquals('Podcast episode', $field_group['group_podcast_episode']['label']);
    $this->assertEquals('fieldset', $field_group['group_podcast_episode']['format_type']);

    // Check role field definitions for a Schema.org mapping.
    $mapping = SchemaDotOrgMapping::load('node.podcast_episode');
    $expected_field_definitions = [
      'host' => [
        'schema_type' => 'PodcastEpisode',
        'schema_property' => 'actor',
        'field_name' => 'schema_role_host',
        'label' => 'Hosts',
        'description' => 'Person responsible for guests at an event.',
        'role_name' => 'Host',
      ],
      'guest' => [
        'schema_type' => 'PodcastEpisode',
        'schema_property' => 'actor',
        'field_name' => 'schema_role_guest',
        'label' => 'Guests',
        'description' => 'Person visiting or attending an event.',
        'role_name' => 'Guest',
      ],
    ];
    $this->assertEquals(
      $expected_field_definitions,
      $this->roleManager->getMappingFieldDefinitions($mapping)
    );
  }

}
