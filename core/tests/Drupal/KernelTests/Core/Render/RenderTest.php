<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render;

use Drupal\KernelTests\KernelTestBase;

/**
 * Performs functional tests on \Drupal::service('renderer')->render().
 *
 * @group Common
 */
class RenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'common_test', 'theme_test'];

  /**
   * Tests theme preprocess functions being able to attach assets.
   */
  public function testDrupalRenderThemePreprocessAttached(): void {
    \Drupal::state()->set('theme_preprocess_attached_test', TRUE);

    $test_element = [
      '#theme' => 'common_test_render_element',
      'foo' => [
        '#markup' => 'Kittens!',
      ],
    ];
    \Drupal::service('renderer')->renderRoot($test_element);

    $expected_attached = [
      'library' => [
        'test/generic_preprocess',
        'test/specific_preprocess',
      ],
    ];
    $this->assertEquals($expected_attached, $test_element['#attached'], 'All expected assets from theme preprocess hooks attached.');

    \Drupal::state()->set('theme_preprocess_attached_test', FALSE);
  }

  /**
   * Ensures that render array children are processed correctly.
   */
  public function testRenderChildren(): void {
    // Ensure that #prefix and #suffix is only being printed once since that is
    // the behavior the caller code expects.
    $build = [
      '#type' => 'container',
      '#theme' => 'theme_test_render_element_children',
      '#prefix' => 'kangaroo',
      '#suffix' => 'kitten',
    ];
    $this->render($build);
    $this->removeWhiteSpace();
    $this->assertNoRaw('<div>kangaroo-kitten</div>');
  }

  /**
   * Tests that we get an exception when we try to attach an illegal type.
   */
  public function testProcessAttached(): void {
    // Specify invalid attachments in a render array.
    $build['#attached']['library'][] = 'core/drupal.states';
    $build['#attached']['drupal_process_states'][] = [];
    $renderer = $this->container->get('bare_html_page_renderer');
    $this->expectException(\LogicException::class);
    $renderer->renderBarePage($build, '', 'maintenance_page');
  }

  /**
   * Tests the deprecation of \Drupal\Core\Render\Renderer::renderPlain()
   *
   * @group legacy
   */
  public function testDeprecateRenderPlain(): void {
    $message = ['#markup' => 'Test'];
    \Drupal::service('renderer')->renderPlain($message);
    $this->expectDeprecation('Renderer::renderPlain() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Instead, you should use ::renderInIsolation(). See https://www.drupal.org/node/3407994');
  }

}
