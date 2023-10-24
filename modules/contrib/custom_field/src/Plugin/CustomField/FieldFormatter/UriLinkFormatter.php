<?php

namespace Drupal\custom_field\Plugin\CustomField\FieldFormatter;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\custom_field\Plugin\CustomFieldFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "uri_link",
 *   label = @Translation("Link"),
 *   field_types = {
 *     "uri"
 *   }
 * )
 */
class UriLinkFormatter implements CustomFieldFormatterInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static();
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    $plugin->entityRepository = $container->get('entity.repository');
    $plugin->renderer = $container->get('renderer');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '80',
      'url_plain' => FALSE,
      'rel' => '',
      'target' => '',
      'title' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, array $settings) {
    $default_settings = self::defaultSettings();
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Leave blank to render the url.'),
      '#default_value' => $settings['title'] ?? $default_settings['title'],
    ];
    $elements['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim link text length'),
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $settings['trim_length'] ?? $default_settings['trim_length'],
      '#min' => 1,
      '#description' => $this->t('Leave blank to allow unlimited link text lengths.'),
    ];
    $elements['url_plain'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show URL as plain text'),
      '#default_value' => $settings['url_plain'] ?? $default_settings['url_plain'],
    ];
    $elements['rel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $settings['rel'] ?? $default_settings['rel'],
    ];
    $elements['target'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $settings['target'] ?? $default_settings['target'],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    return $summary;
  }

  /**
   * Builds the \Drupal\Core\Url object for a link field item.
   *
   * @param array $settings
   *   An array of settings to build the url from.
   *
   * @return \Drupal\Core\Url
   *   A Url object.
   */
  protected function buildUrl(array $settings) {
    $formatter_settings = $settings['formatter_settings'] ?? self::defaultSettings();
    try {
      $url = $this->getUrl($settings['value']);
    }
    catch (\InvalidArgumentException $e) {
      // @todo Add logging here in https://www.drupal.org/project/drupal/issues/3348020
      $url = Url::fromRoute('<none>');
    }

    $options = $url->getOptions();

    // Add optional 'rel' attribute to link options.
    if (!empty($formatter_settings['rel'])) {
      $options['attributes']['rel'] = $formatter_settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($formatter_settings['target']) && $url->isExternal()) {
      $options['attributes']['target'] = $formatter_settings['target'];
    }
    $url->setOptions($options);

    return $url;
  }

  /**
   * Helper function to get a Url from given string value.
   *
   * @param string $value
   *   The field value.
   *
   * @return \Drupal\Core\Url
   *   The Url object.
   */
  protected function getUrl(string $value) {
    return Url::fromUri($value);
  }

  /**
   * Helper function to determine if Url is external.
   *
   * @param string $value
   *   The uri value to test.
   *
   * @return bool
   *   The boolean value for if the url is external.
   */
  protected function isExternal(string $value) {
    return $this->getUrl($value)->isExternal();
  }

  /**
   * {@inheritdoc}
   */
  public function formatValue(array $settings) {
    $formatter_settings = $settings['formatter_settings'] ?? self::defaultSettings();
    $url = $this->buildUrl($settings);
    // Use the full URL as the link title by default.
    $link_title = $url->toString();
    if ($url->isRouted() && preg_match('/^entity\.(\w+)\.canonical$/', $url->getRouteName(), $matches)) {
      // Check access to the canonical entity route.
      $link_entity_type = $matches[1];
      if (!empty($url->getRouteParameters()[$link_entity_type])) {
        $link_entity = NULL;
        $link_entity_param = $url->getRouteParameters()[$link_entity_type];
        if ($link_entity_param instanceof EntityInterface) {
          $link_entity = $link_entity_param;
        }
        elseif (is_string($link_entity_param) || is_numeric($link_entity_param)) {
          try {
            $link_entity_type_storage = $this->entityTypeManager->getStorage($link_entity_type);
            $link_entity = $link_entity_type_storage->load($link_entity_param);
          }
          catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          }
        }
        // Set the entity in the correct language for display.
        if ($link_entity instanceof TranslatableInterface) {
          $link_entity = $this->entityRepository->getTranslationFromContext($link_entity, $settings['langcode']);
        }
        if ($link_entity instanceof EntityInterface) {
          $access = $link_entity->access('view', NULL, TRUE);
          // Add the access result's cacheability, ::view() needs it.
          // How do we handle this?
          // $item->_accessCacheability =
          // CacheableMetadata::createFromObject($access);
          $link_title = $link_entity->label();
          if (!$access->isAllowed()) {
            return NULL;
          }
        }
      }
    }

    // Trim the link text to the desired length.
    if (!empty($settings['trim_length'])) {
      $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
    }

    // If the title field value is available, use it for the link text.
    if (!empty($formatter_settings['title'])) {
      $link_title = $formatter_settings['title'];
    }
    if ($formatter_settings['url_plain']) {
      $build = [
        '#plain_text' => $settings['value'],
      ];
    }
    else {
      $build = [
        '#type' => 'link',
        '#title' => $link_title,
        '#options' => $url->getOptions(),
        '#url' => $url,
      ];
    }

    return $this->renderer->render($build);
  }

}
