<?php

namespace Drupal\fivestar;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Contains methods for managing votes.
 */
class WidgetManager implements WidgetManagerInterface {

  /**
   * Constructs a new VoteManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getWidgets(): array {
    $widgets = &drupal_static(__FUNCTION__);
    if (isset($widgets)) {
      return $widgets;
    }

    $widgets = $this->moduleHandler->invokeAll('fivestar_widgets');
    // Invoke hook_fivestar_widgets_alter() to allow all modules to alter the
    // discovered widgets.
    $this->moduleHandler->alter('fivestar_widgets', $widgets);

    return $widgets;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetInfo(string $widget_key): array {
    $widgets_info = $this->getWidgets();
    return $widgets_info[$widget_key] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetLabel(string $widget_key): string {
    if (!$widget_info = $this->getWidgetInfo($widget_key)) {
      return '';
    }

    return $widget_info['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetLibrary(string $widget_key): string {
    if (!$widget_info = $this->getWidgetInfo($widget_key)) {
      return '';
    }

    return $widget_info['library'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetsOptionSet(): array {
    $options = [];
    foreach ($this->getWidgets() as $widget_key => $widget_info) {
      $options[$widget_key] = $widget_info['label'];
    }
    return $options;
  }

}
