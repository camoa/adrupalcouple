<?php

namespace Drupal\fivestar;

/**
 * Contains methods for managing votes.
 */
interface WidgetManagerInterface {

  /**
   * Returns an array of all widgets.
   *
   * @return array
   *   An associative array of widgets and their info.
   *
   * @see hook_fivestar_widgets()
   */
  public function getWidgets(): array;

  /**
   * Returns a widget's info.
   *
   * @param string $widget_key
   *   The key of the target widget.
   *
   * @return array
   *   An array of widget info.
   *
   * @see hook_fivestar_widgets()
   */
  public function getWidgetInfo(string $widget_key): array;

  /**
   * Returns the label for a given widget if it exists.
   *
   * @param string $widget_key
   *   The key of the target widget.
   *
   * @return string
   *   The widget label.
   */
  public function getWidgetLabel(string $widget_key): string;

  /**
   * Returns the library for a given widget if it exists.
   *
   * @param string $widget_key
   *   The key of the target widget.
   *
   * @return string
   *   The library name.
   */
  public function getWidgetLibrary(string $widget_key): string;

  /**
   * Returns an array of field options based on available widgets.
   *
   * @return array
   *   Associative array where the key is the option value and the value is the
   *   option label.
   */
  public function getWidgetsOptionSet(): array;

}
