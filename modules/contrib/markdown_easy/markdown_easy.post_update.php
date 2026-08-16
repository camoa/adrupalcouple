<?php

/**
 * @file
 * Post-update hooks for the Markdown Easy module.
 */

declare(strict_types=1);

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Import new default settings for version 2.0.0.
 */
function markdown_easy_post_update_20000(?array &$sandbox = NULL): TranslatableMarkup {
  $module_handler = \Drupal::service('module_handler');
  $module_path = $module_handler->getModule('markdown_easy')->getPath();
  $settings_file = DRUPAL_ROOT . '/' . $module_path . '/config/optional/markdown_easy.settings.yml';

  if (file_exists($settings_file)) {
    $default_settings = Yaml::decode(file_get_contents($settings_file));

    if (is_array($default_settings)) {
      \Drupal::configFactory()
        ->getEditable('markdown_easy.settings')
        ->setData($default_settings)
        ->save();

      return new TranslatableMarkup('markdown_easy.settings configuration imported from default settings.');
    }
  }

  return new TranslatableMarkup('No markdown_easy.settings default configuration found.');
}
