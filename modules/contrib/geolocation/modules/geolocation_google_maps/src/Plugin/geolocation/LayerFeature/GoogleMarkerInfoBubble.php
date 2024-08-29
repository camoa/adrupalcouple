<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\LayerFeature;

use Drupal\geolocation\LayerFeatureBase;
use Drupal\geolocation\MapProviderInterface;

/**
 * Provides marker infobubble.
 *
 * @LayerFeature(
 *   id = "marker_infobubble",
 *   name = @Translation("Marker InfoBubble"),
 *   description = @Translation("Open InfoBubble on Marker click."),
 *   type = "google_maps",
 * )
 */
class GoogleMarkerInfoBubble extends LayerFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings(): array {
    return [
      'close_button' => FALSE,
      'close_other' => TRUE,
      'close_button_src' => '',
      'shadow_style' => 0,
      'padding' => 10,
      'border_radius' => 8,
      'border_width' => 2,
      'border_color' => '#039be5',
      'background_color' => '#fff',
      'min_width' => '',
      'max_width' => '550',
      'min_height' => '',
      'max_height' => '',
      'arrow_style' => 2,
      'arrow_position' => 30,
      'arrow_size' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents = [], MapProviderInterface $mapProvider = NULL): array {
    $form = parent::getSettingsForm($settings, $parents, $mapProvider);

    $form['close_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a close button in the right upper corner of the infobubble'),
      '#description' => $this->t('This button closes the tooltip.'),
      '#default_value' => $settings['close_button'],
    ];
    $form['close_other'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close other infobubbles when opening this one'),
      '#description' => $this->t('Before opening this infobubble closes the others'),
      '#default_value' => $settings['close_other'],
    ];
    $form['close_button_src'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL for the close button'),
      '#description' => $this->t('A 12x12 pixel image'),
      '#default_value' => $settings['close_button_src'],
    ];
    $form['shadow_style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Change shadow style'),
      '#description' => $this->t('Allow the user to switch the shadow style'),
      '#default_value' => $settings['shadow_style'],
    ];
    $form['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#description' => $this->t('Change padding.'),
      '#default_value' => $settings['padding'],
    ];
    $form['border_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border radius'),
      '#description' => $this->t('Change border radius'),
      '#default_value' => $settings['border_radius'],
    ];
    $form['border_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border width'),
      '#description' => $this->t('Change border width.'),
      '#default_value' => $settings['border_width'],
    ];
    $form['border_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Border color'),
      '#description' => $this->t('Change border color.'),
      '#default_value' => $settings['border_color'],
    ];
    $form['background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background color'),
      '#description' => $this->t('Change background color.'),
      '#default_value' => $settings['background_color'],
    ];
    $form['min_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum width'),
      '#description' => $this->t('Change Minimum width.'),
      '#default_value' => $settings['min_width'],
    ];
    $form['max_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum width'),
      '#description' => $this->t('Change maximum width.'),
      '#default_value' => $settings['max_width'],
    ];
    $form['min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum height'),
      '#description' => $this->t('Change minimum height.'),
      '#default_value' => $settings['min_height'],
    ];
    $form['max_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum height'),
      '#description' => $this->t('Change maximum height.'),
      '#default_value' => $settings['max_height'],
    ];
    $form['arrow_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Arrow style'),
      '#options' => [
        '0' => $this->t('0 - middle'),
        '1' => $this->t('1 - left'),
        '2' => $this->t('2 - right'),
      ],
      '#default_value' => $settings['arrow_style'],
      '#description' => $this->t('Select the arrow style of the infobubble.'),
    ];
    $form['arrow_position'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow position'),
      '#description' => $this->t('Horizontal position in %.'),
      '#default_value' => $settings['arrow_position'],
      '#field_suffix' => '%',
    ];
    $form['arrow_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow height'),
      '#description' => $this->t('Change arrow height in px.'),
      '#default_value' => $settings['arrow_size'],
      '#field_suffix' => 'px',
    ];

    return $form;
  }

}
