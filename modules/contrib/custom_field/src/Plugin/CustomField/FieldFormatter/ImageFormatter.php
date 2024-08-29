<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldTypeInterface;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image' formatter.
 *
 * @FieldFormatter(
 *   id = "image",
 *   label = @Translation("Image"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class ImageFormatter extends EntityReferenceFormatterBase {

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The file url generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->imageStyleStorage = $container->get('entity_type.manager')->getStorage('image_style');
    $instance->currentUser = $container->get('current_user');
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    $instance->typedDataManager = $container->get('typed_data_manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'image_link' => '',
      'image_loading' => [
        'attribute' => 'lazy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $element = parent::settingsForm($form, $form_state, $settings);
    $settings += static::defaultSettings();
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['image_style'],
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $element['image_link'] = [
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['image_link'],
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    ];

    $image_loading = $settings['image_loading'];
    $element['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Image loading'),
      '#weight' => 10,
      '#description' => $this->t('Lazy render images with native image loading attribute (<em>loading="lazy"</em>). This improves performance by allowing browsers to lazily load images.'),
    ];
    $loading_attribute_options = [
      'lazy' => $this->t('Lazy (<em>loading="lazy"</em>)'),
      'eager' => $this->t('Eager (<em>loading="eager"</em>)'),
    ];
    $element['image_loading']['attribute'] = [
      '#title' => $this->t('Image loading attribute'),
      '#type' => 'radios',
      '#default_value' => $image_loading['attribute'],
      '#options' => $loading_attribute_options,
      '#description' => $this->t('Select the loading attribute for images. <a href=":link">Learn more about the loading attribute for images.</a>', [
        ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
      ]),
    ];
    $element['image_loading']['attribute']['lazy']['#description'] = $this->t('Delays loading the image until that section of the page is visible in the browser. When in doubt, lazy loading is recommended.');
    $element['image_loading']['attribute']['eager']['#description'] = $this->t('Force browsers to download an image as soon as possible. This is the browser default for legacy reasons. Only use this option when the image is always expected to render.');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(FieldItemInterface $item, CustomFieldTypeInterface $field, array $settings) {
    $image = $settings['value'];

    if (!$image instanceof FileInterface) {
      return NULL;
    }

    $access = $this->checkAccess($image);
    if (!$access->isAllowed()) {
      return NULL;
    }

    $url = NULL;
    $formatter_settings = $settings['formatter_settings'] + static::defaultSettings();
    $image_link_setting = $formatter_settings['image_link'];
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $item->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $image_uri = $image->getFileUri();
      $url = $this->fileUrlGenerator->generate($image_uri);
    }

    $image_style_setting = $formatter_settings['image_style'];

    // Collect cache tags to be added for each item in the field.
    $base_cache_tags = [];
    if (!empty($image_style_setting)) {
      /** @var \Drupal\image\ImageStyleInterface $image_style */
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $base_cache_tags = $image_style->getCacheTags();
    }

    $cache_tags = Cache::mergeTags($base_cache_tags, $image->getCacheTags());
    $image_loading_settings = $formatter_settings['image_loading'];
    $item_attributes['loading'] = $image_loading_settings['attribute'];

    /** @var \Drupal\custom_field\Plugin\DataType\CustomFieldImage $instance */
    $instance = $this->typedDataManager->getPropertyInstance($item, $field->getName());

    $build_item = (object) [
      'title' => $instance->getTitle(),
      'alt' => $instance->getAlt(),
      'entity' => $image,
      'uri' => $image->getFileUri(),
      'width' => $instance->getWidth(),
      'height' => $instance->getHeight(),
    ];

    $build = [
      '#theme' => 'image_formatter',
      '#item' => $build_item,
      '#item_attributes' => $item_attributes,
      '#image_style' => $image_style_setting,
      '#url' => $url,
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];

    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateFormatterDependencies(array $settings): array {
    $dependencies = parent::calculateFormatterDependencies($settings);
    $style_id = $settings['image_style'];
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      // If this formatter uses a valid image style to display the image, add
      // the image style configuration entity as dependency of this formatter.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onFormatterDependencyRemoval(array $dependencies, array $settings): array {
    $style_id = $settings['image_style'];
    $changed = FALSE;
    $changed_settings = [];
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
        $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
        // If a valid replacement has been provided in the storage, replace the
        // image style with the replacement and signal that the formatter plugin
        // settings were updated.
        if ($replacement_id && ImageStyle::load($replacement_id)) {
          $settings['image_style'] = $replacement_id;
          $changed = TRUE;
        }
      }
    }
    if ($changed) {
      $changed_settings = $settings;
    }

    return $changed_settings;
  }

}