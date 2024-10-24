<?php

namespace Drupal\social_share\Plugin\SocialShareLink;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Template\Attribute;
use Drupal\social_share\SocialShareLinkInterface;

/**
 * A social share link for PDF.
 *
 * @SocialShareLink(
 *   id = "link_pdf",
 *   label = @Translation("PDF document link"),
 *   category = @Translation("Default"),
 *   context_definitions = {
 *     "pdf_link_text" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("PDF document link text"),
 *       description = @Translation("The text of the PDF document link."),
 *       default_value = "PDF"
 *     ),
 *     "url" = @ContextDefinition(
 *       value = "uri",
 *       label = @Translation("Shared URL"),
 *       description = @Translation("The URL to share. Defaults to the current page."),
 *       required = false
 *     ),
 *     "pdf_url_query_parameter" = @ContextDefinition(
 *       value = "string",
 *       label = @Translation("PDF document URL query parameter"),
 *       description = @Translation("Some ampersand(&)-separated parameters and values to pass with the PDF document URL."),
 *       required = false,
 *       default_value = "pdf=1"
 *     ),
 *   }
 * )
 */
class PDFShareLink extends PluginBase implements SocialShareLinkInterface {

  use ContextAwarePluginTrait;

  /**
   * The machine name of the template used.
   *
   * @var string
   */
  protected $templateName = 'social_share_link_pdf';

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
