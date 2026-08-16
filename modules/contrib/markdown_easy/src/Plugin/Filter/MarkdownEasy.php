<?php

declare(strict_types=1);

namespace Drupal\markdown_easy\Plugin\Filter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple Markdown text filter.
 *
 * @Filter(
 *   id = "markdown_easy",
 *   title = @Translation("Markdown Easy"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   description = @Translation("A simple Markdown filter. You will need to configure the 'Limit allowed HTML tags and correct faulty HTML' filter so that it runs after this filter. See the README to disable this requirement."),
 *   settings = {
 *     "flavor" = "standard"
 *   },
 * )
 */
class MarkdownEasy extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a MarkdownEasy object.
   *
   * @param array<mixed> $configuration
   *   The configuration of the filter.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal core configuration factory.
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      // Default flavor.
      'flavor' => 'standard',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['flavor'] = [
      '#type' => 'select',
      '#title' => $this->t('Flavor'),
      '#default_value' => $this->settings['flavor'],
      '#required' => TRUE,
      '#options' => [
        'standard' => $this->t('Standard Markdown'),
        'github' => $this->t('GitHub-flavored Markdown'),
        'markdownsmorgasbord' => $this->t('Markdown Smörgåsbord'),
      ],
    ];

    $skip_filter_enforcement = $this->configFactory->get('markdown_easy.settings')->get('skip_filter_enforcement');
    if (!$skip_filter_enforcement) {
      $form['tips'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t('Tips'),
        '#items' => [
          $this->t('The Markdown Easy filter should run before the "Limit allowed HTML tags and correct faulty HTML" filter. It is required to use these filters together.'),
          $this->t('<em>Standard Markdown</em> - ensure the following tags (and attributes) are allowed in the "Limit allowed HTML tags" filter for full support: &lt;p&gt; &lt;em&gt; &lt;strong&gt; &lt;a href title&gt; &lt;img alt src title&gt; &lt;code&gt; &lt;pre&gt; &lt;blockquote&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt; &lt;h1&gt; &lt;h2&gt; &lt;h3&gt; &lt;h4&gt; &lt;h5&gt; &lt;h6&gt; &lt;hr&gt; &lt;br&gt;'),
          $this->t('<em>GitHub-flavored Markdown</em> includes the following extensions: Autolinks, Disallowed Raw HTML, Strikethrough, Tables, and Task Lists. Ensure the following tags (and attributes) are allowed in the "Limit allowed HTML tags" filter for full support: &lt;del&gt; &lt;table&gt; &lt;thead&gt; &lt;tbody> &lt;tr&gt; &lt;th class&gt; &lt;td class&gt; &lt;input type checked disabled&gt;'),
          $this->t('<em>Markdown Smörgåsbord</em> includes everything from <em>GitHub-flavored Markdown</em> plus the following extensions: Footnotes, Description lists. Ensure the following tags (and attributes) from the "GitHub-flavored Markdown" list and the following are allowed in the "Limit allowed HTML tags" filter for full support: &lt;sup id&gt; &lt;dl&gt; &lt;dt&gt; &lt;dd&gt; &lt;li class id role&gt; &lt;a class href role&gt;'),
          $this->t('The "Convert line breaks into HTML" filter is no longer recommended for use with the Markdown Easy filter.'),

        ],
        '#attributes' => ['class' => 'form-item__description'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $skip_html_input_stripping = $this->configFactory->get('markdown_easy.settings')->get('skip_html_input_stripping');
    $html_input = $skip_html_input_stripping ? 'allow' : 'strip';
    $config = [
      'html_input' => $html_input,
      'allow_unsafe_links' => FALSE,
      'footnote' => [
        'backref_class'      => 'footnote-backref',
        'backref_symbol'     => '↩',
        'container_add_hr'   => TRUE,
        'container_class'    => 'footnotes',
        'ref_class'          => 'footnote-ref',
        'ref_id_prefix'      => 'fnref_',
        'footnote_class'     => 'footnote',
        'footnote_id_prefix' => 'fn_',
      ],
      'table' => [
        'alignment_attributes' => [
          'left' => ['class' => 'markdown-align-left'],
          'center' => ['class' => 'markdown-align-center'],
          'right' => ['class' => 'markdown-align-right'],
        ],
      ],
    ];

    // Allow other modules to modify the configuration.
    $this->moduleHandler->invokeAll('markdown_easy_config_modify', [
      &$config,
    ]);

    $environment = new Environment($config);
    $environment->addExtension(new CommonMarkCoreExtension());

    if ($this->settings['flavor'] == 'github') {
      $environment->addExtension(new GithubFlavoredMarkdownExtension());
    }
    elseif ($this->settings['flavor'] == 'markdownsmorgasbord') {
      $environment->addExtension(new GithubFlavoredMarkdownExtension());
      $environment->addExtension(new FootnoteExtension());
      $environment->addExtension(new DescriptionListExtension());
    }

    // Allow other modules to modify the environment.
    $this->moduleHandler->invokeAll('markdown_easy_environment_modify', [
      &$environment,
    ]);

    $converter = new MarkdownConverter($environment);
    $converted = $converter->convert($text)->__toString();
    $result = new FilterProcessResult($converted);
    // Check if converted text contains aligned table cells, and include table
    // library if required.
    if (preg_match('/<t(d|h) class=\"markdown-align-(left|center|right)\">/', $converted)) {
      $result->addAttachments([
        'library' => [
          'markdown_easy/markdown_easy_filter_table',
        ],
      ]);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE): string {
    return (string) $this->t('Parses markdown and converts it to HTML.');
  }

}
