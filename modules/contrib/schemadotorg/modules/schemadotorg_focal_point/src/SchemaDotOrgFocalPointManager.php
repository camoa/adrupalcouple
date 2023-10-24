<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_focal_point;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The Schema.org focal point manager.
 */
class SchemaDotOrgFocalPointManager implements SchemaDotOrgFocalPointManagerInterface {

  /**
   * Constructs a SchemaDotOrgFocalPointManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resetImageStyles(array $settings, ?array $original_settings = NULL): void {
    $config = $this->configFactory->getEditable('schemadotorg_focal_point.settings');

    /** @var \Drupal\image\ImageStyleStorageInterface $image_style_storage */
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');

    // Delete removed image styles.
    $original_settings = $original_settings ?? $config->get('image_styles');
    $deleted_settings = array_diff_key(
      $original_settings,
      $settings
    );
    if ($deleted_settings) {
      $deleted_image_style_names = array_keys($deleted_settings);
      $pattern = '/^(' . implode('|', $deleted_image_style_names) . ')/';
      $image_styles = $image_style_storage->loadMultiple();
      foreach ($image_styles as $image_style_id => $image_style) {
        if (preg_match($pattern, $image_style_id)) {
          $image_style->delete();
        }
      }
    }

    // Load or create new image styles.
    foreach ($settings as $prefix => $setting) {
      $ratio = $setting['ratio'];
      [$x, $y] = explode(':', $ratio);

      $max_width = $setting['max-width'];
      $min_width = $setting['min-width'] ?? $setting['max-width'];
      $increment = $setting['increment'] ?? 100;

      $width = $max_width;
      while ($width >= $min_width) {
        $height = ceil(($width / $x) * $y);
        $image_style_id = $prefix . 'w' . $width;
        $image_style = $image_style_storage->load($image_style_id)
          ?? $image_style_storage->create(['name' => $image_style_id]);

        $image_style->set('label', "$ratio ({$width}×{$height})");
        $image_style->set('effects', []);
        $image_style->addImageEffect([
          'id' => 'focal_point_scale_and_crop',
          'data' => [
            'crop_type' => 'focal_point',
            'width' => $width,
            'height' => $height,
          ],
        ]);
        $image_style->save();

        $width -= $increment;
      }
    }

    $config->set('image_styles', $settings);
    $config->save();
  }

  /**
   * Get image style name from image style label.
   *
   * @param string $label
   *   The image style label.
   *
   * @return string
   *   The image style name.
   */
  protected function getImageStyleName(string $label): string {
    $label = str_replace(':', 'x', $label);
    return preg_replace('/[^a-z0-9_]+/', '_', strtolower($label));
  }

}
