<?php

/**
 * @file
 * Views integration.
 */

use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_data().
 */
function editoria11y_views_data(): array {
  return [
    'ed11y_page' => [
      'table' => [
        'group' => t('Editoria11y'),
        'provider' => 'editoria11y',
        'base' => [
          'field' => 'pid',
          'title' => t('Editoria11y - Pages with issues'),
          'help' => t('Provides relationship for issues and dismissals', [], ['context' => 'problems']),
          'weight' => -10,
        ],
      ],
      'pid' => [
        'real field' => 'pid',
        'title' => t('EID'),
        'help' => t("Editoria11y's internal page ID"),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
      ],
      'entity_id' => [
        'real field' => 'entity_id',
        'title' => t('Entity ID'),
        'help' => t('The NID/TID/UID of the page with an issue.'),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
      ],
      'entity_type' => [
        'real field' => 'entity_type',
        'title' => t('Entity type'),
        'help' => t('The entity type; "route" if no type found.'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'in_operator',
          'options callback' => '\Drupal\editoria11y\Dashboard::getEntityTypeOptions',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'route_name' => [
        'real field' => 'route_name',
        'title' => t('Route name'),
        'help' => t('Route name for page.'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'page_path' => [
        'real field' => 'page_path',
        'title' => t('Page path'),
        'help' => t('Internal, relative page path.'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'langcode' => [
        'real field' => 'page_language',
        'title' => t('Page language'),
        'help' => t('Active translation.'),
        'field' => [
          'id' => 'language',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'page_title' => [
        'real field' => 'page_title',
        'title' => t('Page title'),
        'help' => t('The name of the route where this was last seen.'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'content_results' => [
        'real field' => 'content_results',
        'title' => t('Content alerts'),
        'help' => t('Number of alerts shown to content editors.'),
        'field' => [
          'id' => 'editoria11y_issues_by_page_link',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
      ],
      'dev_results' => [
        'real field' => 'dev_results',
        'title' => t('Dev alerts'),
        'help' => t('Number of alerts shown to developers.'),
        'field' => [
          'id' => 'editoria11y_issues_by_page_link',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
      ],
      'updated' => [
        'real field' => 'updated',
        'title' => t('Updated'),
        'help' => t('When this page was last checked.'),
        'field' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'filter' => [
          'id' => 'date',
        ],
      ],
    ],
    'ed11y_result' => [
      'table' => [
        'group' => t('Editoria11y'),
        'provider' => 'editoria11y',
        'base' => [
          'field' => 'id',
          'title' => t('Editoria11y - Test results'),
          'help' => t('Stores Editoria11y issue list', [], ['context' => 'problems']),
          'weight' => -10,
        ],
      ],
      'id' => [
        'real field' => 'id',
        'title' => t('Result row'),
        'help' => t('Internal identifier'),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
      ],
      'created' => [
        'real field' => 'created',
        'title' => t('Created'),
        'help' => t('When this page was flagged.'),
        'field' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'filter' => [
          'id' => 'date',
        ],
      ],
      'result_name' => [
        'real field' => 'result_name',
        'title' => t('Alert type'),
        'help' => t('The name of the test as reported by Editoria11y JS'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'result_key' => [
        'real field' => 'result_key',
        'title' => t('Alert type, linked'),
        'help' => t('Linked to report of all alerts of this type.'),
        'field' => [
          'id' => 'editoria11y_pages_by_issue_link',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'in_operator',
          'options callback' => '\Drupal\editoria11y\Dashboard::getResultNameOptions',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'content_count' => [
        'real field' => 'content_count',
        'title' => t('Content alert count'),
        'help' => t('The number of hits for this test type on this page'),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'dev_count' => [
        'real field' => 'dev_count',
        'title' => t('Dev alert count'),
        'help' => t('The number of hits for this test type on this page'),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
    ],
    'ed11y_action' => [
      'table' => [
        'group' => t('Editoria11y'),
        'provider' => 'editoria11y',
        'base' => [
          'field' => 'id',
          'title' => t('Editoria11y - Dismissals'),
          'help' => t('Stores Editoria11y warnings and dismissals'),
          'weight' => -10,
        ],
      ],
      'id' => [
        'real field' => 'id',
        'title' => t('Dismissal internal reference ID'),
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'pid' => [
        'real field' => 'pid',
        'title' => t('Page with dismissal'),
        'help' => 'Page affected',
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'uid' => [
        'real field' => 'uid',
        'title' => t('Dismissed by'),
        'help' => t('The user linked to this dismissal.'),
        'relationship' => [
          'base' => 'users_field_data',
          'base field' => 'uid',
          'id' => 'standard',
          'label' => t('Linked Drupal user'),
        ],
        'field' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'user_name',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'element_id' => [
        'real field' => 'element_id',
        'title' => t('Dismissed element id'),
        'help' => t('Code sample to identify the flagged element.'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'created' => [
        'real field' => 'created',
        'title' => t('Dismissed on'),
        'help' => t('The Unix timestamp of the first time this record was flagged.'),
        'field' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'filter' => [
          'id' => 'date',
        ],
      ],
      'stale_date' => [
        'real field' => 'stale_date',
        'title' => t('Dismissal stale since'),
        'help' => t('The element has not been seen since this date.'),
        'field' => [
          'id' => 'date',
        ],
        'sort' => [
          'id' => 'date',
        ],
        'filter' => [
          'id' => 'date',
        ],
      ],
      'result_name' => [
        'real field' => 'result_name',
        'title' => t('Dismissed result name'),
        'help' => t('The name of the test as reported by Editoria11y JS'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'result_key' => [
        'real field' => 'result_key',
        'title' => t('Dismissed result name, linked'),
        'help' => t('Links to report of all alerts of this type.'),
        'field' => [
          'id' => 'editoria11y_dismissals_by_issue_link',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'in_operator',
          'options callback' => '\Drupal\editoria11y\Dashboard::getDismissalNameOptions',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
      'action_type' => [
        'real field' => 'action_type',
        'title' => t('Dismissal status'),
        'help' => t('The type of dismissal (e.g., OK, Ignore)'),
        'field' => [
          'id' => 'standard',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'filter' => [
          'id' => 'in_operator',
          'options callback' => '\Drupal\editoria11y\Dashboard::getDismissalOptions',
        ],
        'argument' => [
          'id' => 'string',
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_views_data_alter().
 */
function editoria11y_views_data_alter(array &$data): void {
  // Bidirectional relationships from pids to nodes, terms and users.
  $data['ed11y_page']['editoria11y_node_to_page'] = [
    'title' => t('Relate from Editoria11y page to referenced node'),
    'help' => t('Issues were found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'node_field_data',
      // Database field name in example_table for the join.
      'base field' => 'nid',
      // Real database field name in foo for the join, to override.
      'field' => 'entity_id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y page to referenced node'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this node'),
      'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],
    ],
  ];
  $data['node_field_data']['editoria11y_page_to_node'] = [
    'title' => t('Relate from node data to Editoria11y page'),
    'help' => t('Issues were found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_page',
      // Database field name in example_table for the join.
      'base field' => 'entity_id',
      // Real database field name in foo for the join, to override.
      'field' => 'nid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from node data to Editoria11y page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this node'),
      'extra' => [
        0 => [
          'field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],
    ],
  ];
  $data['ed11y_page']['editoria11y_term_to_page'] = [
    'title' => t('Relate from Editoria11y page to referenced taxonomy term'),
    'help' => t('Issues were found on this term page'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'taxonomy_term_field_data',
      // Database field name in example_table for the join.
      'base field' => 'tid',
      // Real database field name in foo for the join, to override.
      'field' => 'entity_id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y page to referenced taxonomy term'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this term page'),
      'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.taxonomy_term.canonical',
        ],
      ],
    ],
  ];
  $data['taxonomy_term_field_data']['editoria11y_page_to_term'] = [
    'title' => t('Relate from taxonomy term data to Editoria11y page'),
    'help' => t('Issues were found on this term page'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_page',
      // Database field name in example_table for the join.
      'base field' => 'entity_id',
      // Real database field name in foo for the join, to override.
      'field' => 'tid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from taxonomy term data to Editoria11y page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this term page'),
      'extra' => [
        0 => [
          'field' => 'route_name',
          'value' => 'entity.taxonomy_term.canonical',
        ],
      ],
    ],
  ];
  $data['ed11y_page']['editoria11y_user_to_page'] = [
    'title' => t('Relate from Editoria11y page list to user bio page data'),
    'help' => t('Issues were found on this user bio page'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'users_field_data',
      // Database field name in example_table for the join.
      'base field' => 'uid',
      // Real database field name in foo for the join, to override.
      'field' => 'entity_id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y page to user bio page with issues'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this user bio page'),
      'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.user.canonical',
        ],
      ],
    ],
  ];
  $data['users_field_data']['editoria11y_page_to_user'] = [
    'title' => t('Relate from user bio page to Editoria11y results'),
    'help' => t('Issues were found on this user bio page'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_page',
      // Database field name in example_table for the join.
      'base field' => 'entity_id',
      // Real database field name in foo for the join, to override.
      'field' => 'uid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from users list to Editoria11y results on bio page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues were found on this user bio page'),
      'extra' => [
        0 => [
          'field' => 'route_name',
          'value' => 'entity.user.canonical',
        ],
      ],
    ],
  ];

  // Bidirectional relationships from pids to results and dismissals.
  $data['ed11y_page']['editoria11y_page_to_results'] = [
    'title' => t('Relate from Editoria11y page to issues on page'),
    'help' => t('Issues found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_result',
      // Database field name in example_table for the join.
      'base field' => 'pid',
      // Real database field name in foo for the join, to override.
      'field' => 'pid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y page to issues on page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues found on this node'),
      /*'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],*/
    ],
  ];
  $data['ed11y_result']['editoria11y_result_to_page'] = [
    'title' => t('Relate from Editoria11y result to page'),
    'help' => t('Issues found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_page',
      // Database field name in example_table for the join.
      'base field' => 'pid',
      // Real database field name in foo for the join, to override.
      'field' => 'pid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y result to page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues found on this node'),
      /*'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],*/
    ],
  ];
  $data['ed11y_page']['editoria11y_dismissal_to_page'] = [
    'title' => t('Relate from Editoria11y page to dismissals on page'),
    'help' => t('Issues found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_action',
      // Database field name in example_table for the join.
      'base field' => 'pid',
      // Real database field name in foo for the join, to override.
      'field' => 'pid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y page to dismissals on page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues found on this node'),
      /*'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],*/
    ],
  ];
  $data['ed11y_action']['editoria11y_page_to_dismissals'] = [
    'title' => t('Relate from Editoria11y dismissal to page'),
    'help' => t('Issues found on this node'),
    'group' => 'Editoria11y',
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'ed11y_page',
      // Database field name in example_table for the join.
      'base field' => 'pid',
      // Real database field name in foo for the join, to override.
      'field' => 'pid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'title' => t('Relate from Editoria11y dismissal to page'),
      // Description shown within the add relationship handler in the UI.
      'help' => t('Issues found on this node'),
      /*'extra' => [
        0 => [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
        ],
      ],*/
    ],
  ];

  // Group module bridge: expose joins from ed11y_page directly to
  // group_relationship rows. Gated so Group remains a soft dependency.
  // Site builders add this relationship, then add Group's own "Group"
  // relationship on top to reach gid / group label fields and filters.
  if (\Drupal::moduleHandler()->moduleExists('group')) {
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_relation_manager */
    $group_relation_manager = \Drupal::service('group_relation_type.manager');

    // Group's entity_id column is shared across all entity types, so the
    // discriminator is plugin_id (e.g. group_node:article, group_user). Build
    // an IN-list per entity type from whatever plugins are registered, so we
    // cover all bundles and any contrib term plugins without hardcoding.
    $plugins_by_entity_type = [];
    foreach ($group_relation_manager->getDefinitions() as $plugin_id => $definition) {
      $plugins_by_entity_type[$definition->getEntityTypeId()][] = $plugin_id;
    }

    $group_bridges = [
      'node' => [
        'route' => 'entity.node.canonical',
        'title' => t('Group relationship for node with Editoria11y issues'),
        'help' => t('Bridge to the Group module. Add the Group module Group relationship on top of this one to filter or sort issues by group.'),
        'label' => t('Group relationship for node'),
      ],
      'user' => [
        'route' => 'entity.user.canonical',
        'title' => t('Group relationship for user bio with Editoria11y issues'),
        'help' => t('Bridge to the Group module. Add the Group module Group relationship on top of this one to filter or sort issues by group.'),
        'label' => t('Group relationship for user'),
      ],
      'taxonomy_term' => [
        'route' => 'entity.taxonomy_term.canonical',
        'title' => t('Group relationship for taxonomy term with Editoria11y issues'),
        'help' => t('Bridge to the Group module. Add the Group module Group relationship on top of this one to filter or sort issues by group. Requires a contrib module that exposes terms as group content.'),
        'label' => t('Group relationship for taxonomy term'),
      ],
    ];

    foreach ($group_bridges as $entity_type_id => $bridge) {
      if (empty($plugins_by_entity_type[$entity_type_id])) {
        continue;
      }
      $data['ed11y_page']['editoria11y_group_relationship_' . $entity_type_id] = [
        'title' => $bridge['title'],
        'help' => $bridge['help'],
        'group' => 'Editoria11y',
        'relationship' => [
          'base' => 'group_relationship_field_data',
          'base field' => 'entity_id',
          'field' => 'entity_id',
          // Group stores entity_id as varchar; ours is int. Core's casted
          // join uses the views.cast_sql service so Postgres (which won't
          // implicitly coerce) gets a portable CAST.
          'id' => 'casted_int_field_join',
          'title' => $bridge['title'],
          'label' => $bridge['label'],
          'help' => $bridge['help'],
          'extra' => [
            [
              'left_field' => 'route_name',
              'value' => $bridge['route'],
            ],
            [
              'field' => 'plugin_id',
              'value' => $plugins_by_entity_type[$entity_type_id],
            ],
          ],
        ],
      ];
    }
  }
}

/**
 * Implements hook_views_pre_render().
 */
function editoria11y_views_pre_render(ViewExecutable $view) {
  // Hide languages column if there is only one language.
  if ($view->id() === 'ed11y_result') {
    $languageManager = \Drupal::languageManager();
    $langSupport = $languageManager->getLanguages();
    if (count(array_keys((array) $langSupport)) < 2) {
      if (!empty($view->field['langcode'])) {
        $view->field['langcode']->options['exclude'] = TRUE;
      }
      elseif (!empty($view->field['langcode_2'])) {
        $view->field['langcode_2']->options['exclude'] = TRUE;
      }
    }
  }
}

/**
 * Implements hook_views_query_alter().
 */
function editoria11y_views_query_alter(ViewExecutable $view, QueryPluginBase $query): void {
  // Search API does not have this method.
  if (method_exists($query, 'getTableQueue')) {
    $tableQueue = $query->getTableQueue();

    // Our relationships need entity ID, type and language.
    foreach ($tableQueue as $queue) {
      if ($queue['join']) {
        if (str_starts_with($queue['join']->leftTable, 'ed11y_page')) {
          $leftTable = $queue['join']->leftTable;
          $table = $queue['alias'];
          $type = FALSE;
          switch ($queue['join']->table) {
            case 'node_field_data':
              $type = 'entity.node.canonical';
              break;

            case 'taxonomy_term_field_data':
              $type = 'entity.taxonomy_term.canonical';
              break;

            case 'users_field_data':
              $type = 'entity.user.canonical';
              break;
          }
          if ($table && $type) {
            // Match the translation whose langcode equals the recorded page
            // language, OR any language-neutral entity (und/zxx). Without the
            // second clause, language-neutral content (e.g. a node saved with
            // langcode 'und') never satisfies "page_language = langcode".
            $queue['join']->extra = "(" . $leftTable . ".page_language = " . $table . ".langcode OR " . $table . ".langcode IN ('und', 'zxx')) AND " . $leftTable . ".route_name = '" . $type . "'";
          }
        }
        elseif ($queue['join']->table === 'ed11y_page') {
          $table = $queue['alias'];
          $leftTable = FALSE;
          $type = 'entity.node.canonical';
          switch ($queue['join']->leftTable) {
            case 'node_field_data':
              $leftTable = 'node_field_data';
              break;

            case 'taxonomy_term_field_data':
              $leftTable = 'taxonomy_term_field_data';
              $type = 'entity.taxonomy_term.canonical';
              break;

            case 'users_field_data':
              $leftTable = 'users_field_data';
              $type = 'entity.user.canonical';
              break;
          }
          if ($table && $leftTable) {
            // See note above: also match language-neutral (und/zxx) entities.
            $queue['join']->extra = "(" . $table . ".page_language = " . $leftTable . ".langcode OR " . $leftTable . ".langcode IN ('und', 'zxx')) AND " . $table . ".route_name = '" . $type . "'";
          }
        }
      }
    }
  }

}

/**
 * Implements hook_views_post_render().
 *
 * Runs after a View is built, with access to the render array so we can inject
 * header content that replaces the Global: Unfiltered text Twig.
 */
function editoria11y_views_post_render(ViewExecutable $view, array &$build, CachePluginBase $cache) {
  // Target your specific view + display.
  if ($view->id() !== 'ed11y_result' ||
    $view->current_display !== 'issues_by_page__page' ||
    empty($view->result) ||
    empty($view->field['page_path']) ||
    !isset($build["#view"]->header["area_text_custom"])
  ) {
    return;
  }

  if (!empty($view->field['view_node']->original_value)) {
    $dynamic_path = $view->field['view_node']->original_value;
  }
  elseif (!empty($view->field['view_user']->original_value)) {
    $dynamic_path = $view->field['view_user']->original_value;
  }
  elseif (!empty($view->field['view_term']->original_value)) {
    $dynamic_path = $view->field['view_term']->original_value;
  }

  if (!empty($dynamic_path) &&
    (string) $dynamic_path !==
    (string) $view->field['page_path']->original_value) {
    $display_url = Html::escape($dynamic_path);

    $warning = t('Warning: possible out of date path. This content may have moved to') . ' ' .
      '<a href="' . $display_url . '">' . $display_url . '</a>';

    $view->attachment_before[] = [
      '#markup' => $warning,
    ];
  }

  $build['#cache']['max-age'] = 0;

}
