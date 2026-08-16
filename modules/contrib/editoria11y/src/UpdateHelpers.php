<?php

namespace Drupal\editoria11y;

/**
 * Provides schema snapshots and string comparisons.
 *
 * @phpstan-consistent-constructor
 */
class UpdateHelpers {

  /**
   * Returns the current table schema, used by hook_schema().
   *
   * Builds on the version 3 snapshot and adds the integrity constraints
   * introduced by editoria11y_update_9020():
   * - ed11y_page gains a path_hash column with a unique key. Uniqueness of
   *   (page_path, page_language) cannot be enforced directly: the pair is
   *   ~4350 utf8mb4 bytes, past MySQL's 3072-byte index limit, so a sha256
   *   of "path|language" is constrained instead.
   * - ed11y_result enforces one row per (pid, result_key), the key set the
   *   report merge() upserts on.
   * - ed11y_action enforces one row per dismissal identity, keyed on the
   *   stable result_key rather than the translatable result_name.
   */
  public static function schemaCurrent(string $array): array {
    $schema = static::schemaVersion3($array);
    switch ($array) {
      case 'ed11y_page':
        $schema['fields']['path_hash'] = [
          'type' => 'varchar_ascii',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
          'description' => 'sha256 of "page_path|page_language", unique per page.',
        ];
        $schema['unique keys'] = [
          'ed11y_path_hash' => ['path_hash'],
        ];
        break;

      case 'ed11y_result':
        $schema['unique keys'] = [
          'ed11y_result_key' => ['pid', 'result_key'],
        ];
        break;

      case 'ed11y_action':
        $schema['unique keys'] = [
          'ed11y_action_key' => [
            'pid',
            'result_key',
            'element_id',
            'action_type',
            'uid',
          ],
        ];
        break;
    }
    return $schema;
  }

  /**
   * Returns table schema for the initial version 3 release.
   *
   * This is a historical snapshot used by the 2.x migration; schema changes
   * belong in schemaCurrent() plus an update hook, never here.
   */
  public static function schemaVersion3(string $array): array {
    return match ($array) {
      'ed11y_page' => [
        'description' => 'Pages with issues detected by Editoria11y',
        'fields' => [
          'pid' => [
            'description' => 'Serial unique ID',
            'type' => 'serial',
            'size' => 'big',
            'not null' => TRUE,
          ],
          'entity_id' => [
            'description' => 'The node, term or user id this record affects.',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
          'entity_type' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'default' => 'node',
            'length' => 32,
            'description' => 'The entity type; "route" if no type found.',
          ],
          'route_name' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'default' => 'unknown',
            'length' => 255,
            'description' => 'Route name for page.',
          ],
          'page_path' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'default' => 'unknown',
            'length' => 1024,
            'description' => 'Internal, relative page path.',
          ],
          'page_language' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'default' => 'unknown',
            'length' => 64,
            'description' => 'Active translation.',
          ],
          'page_title' => [
            'type' => 'varchar',
            'not null' => TRUE,
            'default' => 'unknown',
            'length' => 1024,
            'description' => 'The name of the route where this was last seen.',
          ],
          'content_results' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The total number of issues on this page.',
          ],
          'dev_results' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The total number of issues on this page.',
          ],
          'updated' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The Unix timestamp of the last update.',
          ],
        ],
        'primary key' => [
          'pid',
        ],
        'indexes' => [
          'entity_type' => ['entity_type'],
          'page_path' => ['page_path'],
          'page_language' => ['page_language'],
          'entity_id' => ['entity_id'],
        ],
      ],
      'ed11y_result' => [
        'description' => 'Stores Editoria11y issue list',
        'fields' => [
          'id' => [
            'description' => 'Test result',
            'type' => 'serial',
            'size' => 'big',
            'not null' => TRUE,
          ],
          'pid' => [
            'description' => 'The ed11y page table record this affects',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
          'created' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The Unix timestamp of the first time this record was flagged.',
          ],
          'result_name' => [
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'The title of the test as reported by Editoria11y JS',
          ],
          'result_key' => [
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'The name of the test as reported by editoria11y JS',
          ],
          'content_count' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Number of alerts relevant to content editors.',
          ],
          'dev_count' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'Number of alerts relevant to developers.',
          ],
        ],
        'primary key' => [
          'id',
        ],
        'indexes' => [
          'result_key' => ['result_key'],
          'pid' => ['pid'],
        ],
        'foreign keys' => [
          'pid' => [
            'table' => 'ed11y_page',
            'columns' => [
              'pid' => 'pid',
            ],
          ],
        ],
      ],
      'ed11y_action' => [
        'description' => 'Stores Editoria11y actions',
        'fields' => [
          'id' => [
            'description' => 'Element affected',
            'type' => 'serial',
            'size' => 'big',
            'not null' => TRUE,
          ],
          'pid' => [
            'description' => 'The ed11y page table record this affects.',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
          'uid' => [
            'description' => 'The {users}.uid that took this action.',
            'type' => 'int',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'default' => 0,
          ],
          'element_id' => [
            'type' => 'char',
            'length' => 64,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'Hashed code sample to identify the flagged element.',
          ],
          'created' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
            'description' => 'The Unix timestamp of the first time this record was flagged.',
          ],
          'result_name' => [
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'The title of the test as reported by editoria11y JS',
          ],
          'result_key' => [
            'type' => 'varchar',
            'length' => 255,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'The name of the test as reported by editoria11y JS',
          ],
          'action_type' => [
            'type' => 'varchar_ascii',
            'length' => 64,
            'not null' => TRUE,
            'default' => 'unknown',
            'description' => 'Ignore, ok.',
          ],
          'stale_date' => [
            'type' => 'int',
            'not null' => FALSE,
            'default' => NULL,
            'description' => 'The Unix timestamp when the element disappeared.',
          ],
        ],
        'primary key' => [
          'id',
        ],
        'indexes' => [
          'element_id' => ['element_id'],
          'result_name' => ['result_name'],
          'pid' => ['pid'],
          'uid' => ['uid'],
        ],
        'foreign keys' => [
          'data_user' => [
            'table' => 'users',
            'columns' => [
              'uid' => 'uid',
            ],
          ],
          'pid' => [
            'table' => 'ed11y_page',
            'columns' => [
              'pid' => 'pid',
            ],
          ],
        ],
      ],
    };
  }

  /**
   * Returns test name data fragments.
   */
  public static function updateVersion3(string $array): array {
    return match ($array) {
      // Translate old test strings to new test keys.
      'oldNames' => [
        'Alt text is meaningless' => 'ALT_PLACEHOLDER',
        "Image's text alternative is unpronounceable" => 'ALT_UNPRONOUNCEABLE',
        'Linked Image has no alt text' => 'LINK_IMAGE_NO_ALT_TEXT',
        'Manual check: possibly redundant text in alt' => 'SUS_ALT',
        'Manual check: possibly redundant text in linked image' => 'LINK_SUS_ALT',
        'Manual check: very long alternative text' => 'IMAGE_ALT_TOO_LONG',
        'Image has no alternative text attribute' => 'LINK_IMAGE_LONG_ALT',
        'Manual check: image has no alt text' => 'IMAGE_DECORATIVE',
        'Manual check: link contains both text and an image' => 'LINK_IMAGE_ALT_AND_TEXT',
        "Image's text alternative is a URL" => 'ALT_FILE_EXT',
        "Linked image's text alternative is a URL" => 'LINK_ALT_FILE_EXT',
        'Manual check: is this a blockquote?' => 'QA_BLOCKQUOTE',
        'Manual check: is an accurate transcript provided?' => 'EMBED_AUDIO',
        'Manual check: is this embedded content accessible?' => 'EMBED_GENERAL',
        'Manual check: is this video accurately captioned?' => 'EMBED_VIDEO',
        'Manual check: is this visualization accessible?' => 'EMBED_DATA_VIZ',
        'Heading tag without any text' => 'HEADING_EMPTY',
        'Manual check: long heading' => 'HEADING_LONG',
        'Manual check: was a heading level skipped?' => 'HEADING_SKIPPED_LEVEL',
        'Manual check: is the linked document accessible?' => 'QA_PDF',
        'Manual check: is opening a new window expected?' => 'LINK_NEW_TAB',
        'Link with no accessible text' => 'LINK_EMPTY',
        'Manual check: is this link meaningful and concise?' => 'LINK_STOPWORD',
        'Manual check: is this link text a URL?' => 'LINK_URL',
        'Content heading inside a table' => 'TABLES_SEMANTIC_HEADING',
        'Empty table header cell' => 'TABLES_EMPTY_HEADING',
        'Table has no header cells' => 'TABLES_MISSING_HEADINGS',
        'Manual check: should this be a heading?' => 'QA_FAKE_HEADING',
        'Manual check: should this have list formatting?' => 'QA_FAKE_LIST',
        'Manual check: is this uppercase text needed?' => 'QA_UPPERCASE',
      ],
      // Translate old test keys to new test keys.
      'oldKeys' => [
        'altDeadspace' => 'ALT_UNPRONOUNCEABLE',
        'altEmptyLinked' => 'LINK_IMAGE_NO_ALT_TEXT',
        'altImageOf' => 'SUS_ALT',
        'altImageOfLinked' => 'LINK_SUS_ALT',
        'altLong' => 'IMAGE_ALT_TOO_LONG',
        'altLongLinked' => 'LINK_IMAGE_LONG_ALT',
        'altMeaningless' => 'ALT_PLACEHOLDER',
        'altMeaninglessLinked' => 'LINK_PLACEHOLDER_ALT',
        'altMissing' => 'MISSING_ALT',
        'altNull' => 'IMAGE_DECORATIVE',
        'altPartOfLinkWithText' => 'LINK_IMAGE_ALT_AND_TEXT',
        'altURL' => 'ALT_FILE_EXT',
        'altURLLinked' => 'LINK_ALT_FILE_EXT',
        'blockquoteIsShort' => 'QA_BLOCKQUOTE',
        'embedAudio' => 'EMBED_AUDIO',
        'embedCustom' => 'EMBED_GENERAL',
        'embedVideo' => 'EMBED_VIDEO',
        'embedVisualization' => 'EMBED_DATA_VIZ',
        'headingEmpty' => 'HEADING_EMPTY',
        'headingIsLong' => 'HEADING_LONG',
        'headingLevelSkipped' => 'HEADING_SKIPPED_LEVEL',
        'linkDocument' => 'QA_DOCUMENT',
        'linkNewWindow' => 'LINK_NEW_TAB',
        'linkNoLabel' => 'LINK_EMPTY_NO_LABEL',
        'linkNoText' => 'LINK_EMPTY',
        'linkTextIsGeneric' => 'LINK_STOPWORD',
        'linkTextIsURL' => 'LINK_URL',
        'tableContainsContentHeading' => 'TABLES_SEMANTIC_HEADING',
        'tableEmptyHeaderCell' => 'TABLES_EMPTY_HEADING',
        'tableNoHeaderCells' => 'TABLES_MISSING_HEADINGS',
        'textPossibleHeading' => 'QA_FAKE_HEADING',
        'textPossibleList' => 'QA_FAKE_LIST',
        'textUppercase' => 'QA_UPPERCASE',
      ],
    };
  }

}
