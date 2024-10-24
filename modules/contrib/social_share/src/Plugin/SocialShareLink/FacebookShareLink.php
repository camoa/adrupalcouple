<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for facebook.
 *
 * @SocialShareLink(
 *   id = "social_share_facebook",
 *   label = @Translation("Facebook"),
 *   category = @Translation("Default"),
 *   context_definitions = {
 *     "facebook_app_id" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Facebook app id"),
 *       description = @Translation("The facebook app id to use when generating the link."),
 *     ),
 *     "facebook_link_text" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Facebook link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share on facebook",
 *     ),
 *     "title" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Title"),
 *       description = @Translation("The title of the shared item.")
 *     ),
 *     "description" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Description"),
 *       description = @Translation("The description text to use for sharing."),
 *       required = false
 *     ),
 *     "caption" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Caption"),
 *       description = @Translation("The caption used for sharing."),
 *       required = false
 *     ),
 *     "url" = @ContextDefinition(
 *       value = "uri",
 *       label = @Translation("Shared URL"),
 *       description = @Translation("The URL to share. Defaults to the current page."),
 *       required = false
 *     ),
 *     "media_url" = @ContextDefinition("string",
 *       label = @Translation("Media-Url"),
 *       description = @Translation("The URL of some media to use for sharing."),
 *       required = false
 *     ),
 *     "media_image_url" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Media Image-Url"),
 *       description = @Translation("If some non-image media is shared, an optional preview image to use for sharing."),
 *       required = false
 *     ),
 *     "facebook_ref" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("Facebook ref"),
 *       description = @Translation("Some comma-separated list of extra arguments to pass to facebook."),
 *       required = false
 *     ),
 *   }
 * )
 */
class FacebookShareLink extends PluginBase implements SocialShareLinkInterface {

  use ContextAwarePluginTrait;

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_facebook';

  /**
   * {@inheritdoc}
   */
  public function build($template_suffix = '', $render_context = []) {
    $render = [
      '#theme' => $this->templateName . $template_suffix,
      '#attributes' => new Attribute([]),
      '#render_context' => $render_context,
    ];
    foreach ($this->getContexts() as $name => $context) {
      $render["#$name"] = $context->getContextValue();
    }
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateInfo() {
    $info = [
      'variables' => [
        'render_context' => [],
      ],
      'preprocess functions' => [
        'social_share_preprocess_template_urls',
      ],
    ];
    foreach ($this->getContextDefinitions() as $name => $definition) {
      $info['variables'][$name] = $definition->getDefaultValue();
    }
    return [
      $this->templateName => $info,
    ];
  }

}
