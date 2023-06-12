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
  public function resetImageStyles(array $settings): void {
    $config = $this->configFactory->getEditable('schemadotorg_focal_point.settings');

    /** @var \Drupal\image\ImageStyleStorageInterface $image_style_storage */
    $image_style_storage = $this->entityTypeManager->getStorage('image_style');

    // Delete removed image styles.
    $original_settings = $config->get('image_styles');
    $deleted_settings = array_diff_key(
      $original_settings,
      $settings
    );
    if ($deleted_settings) {
      $deleted_image_style_names = array_keys($deleted_settings);
      $deleted_image_styles = $image_style_storage->loadMultiple($deleted_image_style_names);
      foreach ($deleted_image_styles as $deleted_image_style) {
        $deleted_image_style->delete();
      }
    }

    // Load or create new image styles.
    foreach ($settings as $name => $setting) {
      $label = $setting['label'];
      $data = $setting['data'];

      $image_style = $image_style_storage->load($name)
        ?? $image_style_storage->create(['name' => $name]);

      $image_style->set('label', $label . " ({$data['width']}×{$data['height']})");
      $image_style->set('effects', []);
      $image_style->addImageEffect([
        'id' => 'focal_point_scale_and_crop',
        'data' => $data + [
          'crop_type' => 'focal_point',
        ],
      ]);

      $image_style->save();
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
