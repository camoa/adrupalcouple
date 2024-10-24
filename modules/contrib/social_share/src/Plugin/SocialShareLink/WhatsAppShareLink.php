<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for WhatsApp.
 *
 * @SocialShareLink(
 *   id = "social_share_whatsapp",
 *   label = @Translation("WhatsApp"),
 *   category = @Translation("Default"),
 *   context_definitions = {
 *     "whatsapp_link_text" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("WhatsApp link text"),
 *       description = @Translation("The text of the sharing link."),
 *       default_value = "Share with WhatsApp"
 *     ),
 *    "whatsapp_link_message" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("WhatsApp message"),
 *       description = @Translation("The text of message."),
 *       default_value = "Check this page"
 *     ),
 *     "url" = @ContextDefinition(
 *       value = "uri",
 *       label = @Translation("Shared URL"),
 *       description = @Translation("The URL to share. Defaults to the current page."),
 *       required = true
 *     ),
 *   }
 * )
 */
class WhatsAppShareLink extends PluginBase implements SocialShareLinkInterface {

  use ContextAwarePluginTrait;

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_whatsapp';

  /**
   * {@inheritdoc}
   */
  public function build($template_suffix = '', $render_context = []) {
    $render =  [
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
