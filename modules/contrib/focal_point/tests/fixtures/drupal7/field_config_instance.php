<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.6 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('field_config_instance', array(
  'fields' => array(
    'id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
    ),
    'field_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
    ),
    'field_name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'entity_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'bundle' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'data' => array(
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
  ),
  'primary key' => array(
    'id',
  ),
  'indexes' => array(
    'field_name_bundle' => array(
      'field_name',
      'entity_type',
      'bundle',
    ),
    'deleted' => array(
      'deleted',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('field_config_instance')
->fields(array(
  'id',
  'field_id',
  'field_name',
  'entity_type',
  'bundle',
  'data',
  'deleted',
))
->values(array(
  'id' => '1',
  'field_id' => '1',
  'field_name' => 'comment_body',
  'entity_type' => 'comment',
  'bundle' => 'comment_node_page',
  'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
  'deleted' => '0',
))
->values(array(
  'id' => '2',
  'field_id' => '2',
  'field_name' => 'body',
  'entity_type' => 'node',
  'bundle' => 'page',
  'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";i:-4;s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:8:"settings";a:0:{}s:6:"module";s:4:"text";s:6:"weight";i:0;}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";s:6:"weight";i:0;}}s:8:"required";b:0;s:11:"description";s:0:"";}',
  'deleted' => '0',
))
->values(array(
  'id' => '3',
  'field_id' => '1',
  'field_name' => 'comment_body',
  'entity_type' => 'comment',
  'bundle' => 'comment_node_article',
  'data' => 'a:6:{s:5:"label";s:7:"Comment";s:8:"settings";a:2:{s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:8:"required";b:1;s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";i:0;s:8:"settings";a:0:{}s:6:"module";s:4:"text";}}s:6:"widget";a:4:{s:4:"type";s:13:"text_textarea";s:8:"settings";a:1:{s:4:"rows";i:5;}s:6:"weight";i:0;s:6:"module";s:4:"text";}s:11:"description";s:0:"";}',
  'deleted' => '0',
))
->values(array(
  'id' => '4',
  'field_id' => '2',
  'field_name' => 'body',
  'entity_type' => 'node',
  'bundle' => 'article',
  'data' => 'a:6:{s:5:"label";s:4:"Body";s:6:"widget";a:4:{s:4:"type";s:26:"text_textarea_with_summary";s:8:"settings";a:2:{s:4:"rows";i:20;s:12:"summary_rows";i:5;}s:6:"weight";s:2:"-4";s:6:"module";s:4:"text";}s:8:"settings";a:3:{s:15:"display_summary";b:1;s:15:"text_processing";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:12:"text_default";s:6:"weight";s:1:"0";s:8:"settings";a:0:{}s:6:"module";s:4:"text";}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:23:"text_summary_or_trimmed";s:6:"weight";s:1:"0";s:8:"settings";a:1:{s:11:"trim_length";i:600;}s:6:"module";s:4:"text";}}s:8:"required";b:0;s:11:"description";s:0:"";}',
  'deleted' => '0',
))
->values(array(
  'id' => '6',
  'field_id' => '4',
  'field_name' => 'field_image',
  'entity_type' => 'node',
  'bundle' => 'article',
  'data' => 'a:6:{s:5:"label";s:5:"Image";s:11:"description";s:40:"Upload an image to go with this article.";s:8:"required";i:0;s:8:"settings";a:9:{s:14:"file_directory";s:31:"[date:custom:Y]-[date:custom:m]";s:15:"file_extensions";s:16:"png gif jpg jpeg";s:12:"max_filesize";s:0:"";s:14:"max_resolution";s:0:"";s:14:"min_resolution";s:0:"";s:9:"alt_field";i:1;s:11:"title_field";i:0;s:13:"default_image";i:0;s:18:"user_register_form";b:0;}s:6:"widget";a:5:{s:6:"weight";s:2:"-1";s:4:"type";s:11:"image_image";s:6:"module";s:5:"image";s:6:"active";i:1;s:8:"settings";a:2:{s:18:"progress_indicator";s:8:"throbber";s:19:"preview_image_style";s:19:"focal_point_preview";}}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:5:"image";s:6:"weight";s:2:"-1";s:8:"settings";a:2:{s:11:"image_style";s:2:"fp";s:10:"image_link";s:0:"";}s:6:"module";s:5:"image";}s:6:"teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:5:"image";s:6:"weight";s:2:"-1";s:8:"settings";a:2:{s:11:"image_style";s:19:"focal_point_preview";s:10:"image_link";s:7:"content";}s:6:"module";s:5:"image";}}}',
  'deleted' => '0',
))
->values(array(
  'id' => '11',
  'field_id' => '9',
  'field_name' => 'field_media',
  'entity_type' => 'node',
  'bundle' => 'article',
  'data' => 'a:6:{s:5:"label";s:5:"media";s:6:"widget";a:5:{s:6:"weight";s:1:"0";s:4:"type";s:13:"media_generic";s:6:"module";s:5:"media";s:6:"active";i:1;s:8:"settings";a:4:{s:15:"browser_plugins";a:3:{s:6:"upload";i:0;s:30:"media_default--media_browser_1";i:0;s:37:"media_default--media_browser_my_files";i:0;}s:13:"allowed_types";a:4:{s:5:"image";s:5:"image";s:5:"video";i:0;s:5:"audio";i:0;s:8:"document";i:0;}s:15:"allowed_schemes";a:1:{s:6:"public";s:6:"public";}s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:31:"[date:custom:Y]-[date:custom:m]";s:15:"file_extensions";s:12:"png jpeg jpg";s:12:"max_filesize";s:0:"";s:17:"description_field";i:0;s:18:"user_register_form";b:0;}s:7:"display";a:2:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:13:"file_rendered";s:6:"weight";s:2:"11";s:8:"settings";a:1:{s:14:"file_view_mode";s:7:"default";}s:6:"module";s:11:"file_entity";}s:6:"teaser";a:5:{s:5:"label";s:5:"above";s:4:"type";s:13:"file_rendered";s:6:"weight";s:1:"0";s:8:"settings";a:1:{s:14:"file_view_mode";s:7:"default";}s:6:"module";s:11:"file_entity";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
  'deleted' => '0',
))
->execute();
