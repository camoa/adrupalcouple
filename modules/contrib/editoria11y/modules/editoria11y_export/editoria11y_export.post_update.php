<?php

/**
 * @file
 * Editoria11y Export post update functions.
 */

/**
 * Fixes the export navigation links.
 */
function editoria11y_export_post_update_1001(): void {
  $config = \Drupal::configFactory()->getEditable('views.view.ed11y_export');
  if ($config->isNew()) {
    return;
  }

  $replacements = [
    'href="./pages"' => 'href="./export/pages"',
    'href="./dismissals"' => 'href="./export/dismissals"',
    'href="./alerts"' => 'href="./export/alerts"',
  ];

  $displays = $config->get('display') ?? [];
  $changed = FALSE;
  foreach ($displays as $displayId => $display) {
    // The links live in text_custom area handlers, which can appear in any of
    // these handler groups depending on how a site has customized the view.
    foreach (['header', 'footer', 'empty'] as $area) {
      $handlers = $display['display_options'][$area] ?? [];
      foreach ($handlers as $handlerId => $handler) {
        if (!isset($handler['content']) || !is_string($handler['content'])) {
          continue;
        }
        $updated = strtr($handler['content'], $replacements);
        if ($updated !== $handler['content']) {
          $displays[$displayId]['display_options'][$area][$handlerId]['content'] = $updated;
          $changed = TRUE;
        }
      }
    }
  }

  if ($changed) {
    $config->set('display', $displays)->save(TRUE);
  }
}

/**
 * Backfills the export_filesystem option on the CSV export displays.
 */
function editoria11y_export_post_update_1002(): void {
  // Views Data Export 8.x-1.9 replaced the per-display
  // "store_in_public_file_directory" option with "export_filesystem". VDE ships
  // a migration for this, but as a one-time post_update it never revisits a
  // view installed after it ran, leaving these displays with no valid scheme
  // so DataExport::getTempFile() throws and the "Download CSV" batch dies.
  // Backfill the option here, preserving any scheme an administrator has
  // already chosen. The shared helper lives in the module file.
  \Drupal::moduleHandler()->loadInclude('editoria11y_export', 'module');
  _editoria11y_export_apply_export_filesystem(TRUE);
}
