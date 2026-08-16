<?php

namespace Drupal\editoria11y\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\editoria11y\CSAStatus;
use Drupal\editoria11y\TestNames;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to define all settings of the module.
 *
 * @phpstan-consistent-constructor
 */
class Editoria11ySettings extends ConfigFormBase {

  /**
   * The state service.
   */
  protected StateInterface $state;

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs an Editoria11ySettings object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    StateInterface $state,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->state = $state;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'editoria11y_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'editoria11y.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('editoria11y.settings');
    $permissions = Url::fromRoute('user.admin_permissions');
    $form['#attached']['library'][] = 'editoria11y/editoria11y-settings';

    $installCSA = '';
    if (!$this->moduleHandler->moduleExists('editoria11y_csa') ||
      !CSAStatus::current($this->state)->isActive()) {
      $installCSA = '<li>' . $this->t('Activate the <a href="@csa">Editoria11y CSA submodule</a> to see developer tests.', [
        '@csa' => 'https://editoria11y.com/license',
      ]) . '</li>';
      $csa = FALSE;
    }
    else {
      $csa = TRUE;
      $csaConfig = $this->config('editoria11y_csa.settings');
    }

    $whatToCheck = $csa ?
      $this->t('Include all parts of the page that contain your theme and content.')
      : $this->t('Include all parts of the page that contain your editable content.');

    $form['containers'] = [
      '#type' => 'container',
      // '#open' => FALSE,
      // '#title' => $this->t('Identify elements to check'),
      '#markup' => '<h2>Getting started</h2>'
      // . '<div>' . $this->t('Configuration tips') . '</div>'
      . '<ol><li>'
      // New.
      . $whatToCheck
      . '</li><li>'
      . $this->t('Exclude elements that show unhelpful, repetitive, or inaccurate alerts.')
      . '</li>' . $installCSA . '<li>'
      // $this->t("Make sure") . ' ' .
      // $linkToPermissions . ' ' .
      // $linkToDashboard . ', users need "Manage Editoria11y results.' .
      . $this->t('<a href="@permissions">Add permissions</a>, especially "View Editoria11y checker" and "Mark OK."</a>', [
        '@permissions' => $permissions->toString(),
      ])
      . '</li><li>' . $this->t('If you need help: <a href="@config_guide">Configuration Guide</a> | <a href="@issue_queue">Issue Queue</a> | <a href="@help">Support contacts</a>', [
        '@config_guide' => 'https://editoria11y.com/drupal/',
        '@issue_queue' => 'https://www.drupal.org/project/issues/editoria11y?categories=All',
        '@help' => 'https://editoria11y.com/contacts/',
      ]) . '</li>'
      . '</ol>',
    ];

    $checkRoots = ['automatic', 'match', 'specify'];
    $dev_check_root_options = [
      'automatic' => $this->t("Autodetect"),
      // @todo retranslate
      'match' => $this->t('Only user-editable content'),
      'specify' => $this->t('Other'),
    ];

    $form['containers']['dev_check_root'] = [
      // '#title' => $this->t("Developer check area"),
      '#title' => $this->t("Parts of the page to test"),
      '#type' => $csa ? 'radios' : 'textfield',
      '#options' => $dev_check_root_options,
      '#default_value' => $csa && !empty($csaConfig->get('dev_check_root')) && in_array($csaConfig->get('dev_check_root'), $checkRoots) ? $csaConfig->get('dev_check_root') : 'automatic',
      // Not retranslated in lang files:
      '#description' => $csa ? $this->t('"Autodetect" selects:')
      . '<br><em><code>body > *:not(#toolbar-administration, #drupal-live-announce, .gin-secondary-toolbar, .admin-toolbar, script, style, .ed11y-element)</code></em>' // phpcs:ignore
        : '<ul>' . $installCSA . '</ul>',
      '#disabled' => !$csa,
    ];

    $form['containers']['specify_root'] = [
      '#title' => $this->t("Other: specify regions to check for developer and content problems"),
      // '#title' => $this->t("Specify developer roots"),
      '#type' => 'textarea',
      '#rows' => 2,
      // '#placeholder' => $this->t('Use content area root'),
      '#states' => [
        'visible' => [
          ':input[name="dev_check_root"]' => ['value' => 'specify'],
        ],
      ],
      '#disabled' => !$csa,
      '#default_value' => $csa && $csaConfig ? $csaConfig->get('specify_root') : 'body > *:not(#toolbar-administration, #drupal-live-announce, .gin-secondary-toolbar, .admin-toolbar, script, style, .ed11y-element)',
      // @todo retranslate.
      '#description' => $this->t('Provide CSS selectors. For many sites, the site theme is wrapped in <code>.dialog-off-canvas-main-canvas</code> while editing.'),
    ];

    $form['containers']['always_ignore'] = [
      '#title' => $this->t("Do not check for any errors inside these elements"),
      // '#title' => $this->t("Ignore for both developers and content editors"),
      '#type' => 'textarea',
      '#rows' => 1,
      // @todo retranslate, template filters and storage.
      '#description' => $csa ? $this->t('These parts of the page will be ignored entirely.') : '<ul>' . $installCSA . '</ul>',
      '#default_value' => $csa && $csaConfig ? $csaConfig->get('always_ignore') : '',
      '#placeholder' => $csa ? '' : $this->t('match content settings'),
      // Can't use submodule enum, as submodule may not be available.
      '#disabled' => !CSAStatus::current($this->state)->isActive(),
    ];

    $form['containers']['content_root'] = [
      // '#title' => $this->t("Check content in these containers"),
      '#title' => $this->t("Page regions with user-editable content"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $csa ?
      $this->t('Default: <code><em>main</em></code><br>Provide <a target="_blank" href="https://developer.mozilla.org/en-US/docs/Learn/CSS/Building_blocks/Selectors">CSS selectors</a>. Content authors will only see the alerts within these parts of the page. Do not provide selectors that nest within each other, or the inner content will be checked twice.') :
      $this->t('Default: <code><em>main</em></code><br>Provide <a target="_blank" href="https://developer.mozilla.org/en-US/docs/Learn/CSS/Building_blocks/Selectors">CSS selectors</a>. Do not provide selectors that nest within each other, or the inner content will be checked twice.'),
      '#default_value' => $config->get('content_root'),
    ];

    $form['containers']['ignore_elements'] = [
      // '#title' => $this->t("Skip over these elements"),
      '#title' => $csa ? $this->t("Errors in these elements should only be shown to developers") : $this->t("Do not check for content errors inside these elements"),
      '#type' => 'textarea',
      '#rows' => 1,
      // @todo retranslate
      '#description' => $this->t('Content editors are not responsible for errors in these elements. <br>E.g.: <code><em>#sidebar-menu a, .slide [aria-hidden="true"]</em></code>.'),
      '#default_value' => $config->get('ignore_elements'),
    ];

    $roles = array_filter(Role::loadMultiple(), function (RoleInterface $role) {
      return $role->hasPermission('view editoria11y checker') ||
        $role->hasPermission('administer editoria11y checker') ||
        $role->hasPermission('manage editoria11y results');
    });
    $names = array_map(fn(RoleInterface $role) => $role->label(), $roles);
    $form['containers']['roles'] = [
      '#title' => $this->t('Roles that will see developer alerts'),
      '#type' => 'checkboxes',
      '#options' => $names,
      '#default_value' => $csa ? explode(',', $csaConfig->get('roles')) ?? [] : [],
      '#disabled' => !$csa,
      '#description' => $csa ? $this->t('All roles with the <a href=":url">View editoria11y checker</a> permission see content alerts.', [
        ':url' => $permissions->toString(),
      ])
        : '<ul>' . $installCSA . '</ul>'
      . $this->t('All roles with the <a href=":url">View editoria11y checker</a> permission see content alerts.', [
        ':url' => $permissions->toString(),
      ]),
    ];

    $form['advanced_heading'] = [
      '#type' => 'container',
      '#markup' => '<h2>' . $this->t('Advanced settings') . '</h2>',
    ];

    $form['assertiveness'] = [
      '#type' => 'details',
      '#title' => $this->t('Assertiveness'),
    ];

    $form['assertiveness']['content_assertiveness'] = [
      '#title' => $this->t("Checker mode for content roles"),
      '#type' => 'radios',
      '#options' => [
        'assertive' => $this->t('Start open if there are any alerts'),
        'smart' => $this->t('Start open if there are new alerts'),
        'polite' => $this->t('Start minimized'),
      ],
      '#default_value' => $config->get('assertiveness'),
      '#description' => $this->t('Choose when the control panel should open and show inline tips. "Start open if there are any alerts" is recommended, as it helps tips get noticed over time.'),
    ];

    $form['assertiveness']['dev_assertiveness'] = [
      '#title' => $this->t("Checker mode for developer roles"),
      '#type' => 'radios',
      '#options' => [
        'assertive' => $this->t('Start open if there are any alerts'),
        'smart' => $this->t('Start open if there are new alerts'),
        'polite' => $this->t('Start minimized'),
      ],
      '#default_value' => $csa && $csaConfig ? ($csaConfig->get('assertiveness') ?? 'assertive') : 'assertive',
      '#disabled' => !$csa,
      '#description' => $csa ? '' : '<ul>' . $installCSA . '</ul>',
    ];

    $form['assertiveness']['disable_live'] = [
      '#title' => $this->t("Do not check any content while it is being edited"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('disable_live'),
      '#description' => $this->t('Use the "Do not check on pages with these elements" setting to prevent checking only on specific pages. E.g., exclude: <code><em>form[id^="node-article"]</em></code>.'),
    ];

    $form['assertiveness']['ck5_table_headers'] = [
      '#title' => $this->t("Assign headers to CKEditor tables on insert"),
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'row' => $this->t('First row'),
        'column' => $this->t('First column'),
        'both' => $this->t('Both'),
      ],
      '#default_value' => $config->get('ck5_table_headers'),
      '#description' => $this->t('You will need to clear cache for changes to this setting to appear in CKEditor.'),
    ];

    $form['theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Theme compatibility'),
      '#markup' => '<p>' . $this->t('Configuration tips') . '</p><small><ul><li>' .
      $this->t('If the checker <strong>toggle</strong> does not appear: make sure a z-indexed or overflow-hidden element in your front-end theme is not hiding or covering the <code><em>ed11y-element-panel</em></code> container, make sure that any custom selectors in the "Disable the scanner if these elements are detected" field are not present, and make sure that no JavaScript errors are appearing in your <a href="https://developer.mozilla.org/en-US/docs/Tools/Browser_Console"> browser console</a>') .
        '.</li><li>' . // phpcs:ignore
      $this->t("If the checker toggle is present but <strong>not detecting</strong> errors, or missing errors that should be flagged: check that your inclusions & exclusion settings below are not missing or ignoring the elements. It is not uncommon for homepages or views to insert editable content outside the <code><em>main</em></code> element.") .
      '</li></ul></small>',
    ];

    $form['theme']['ed11y_theme'] = [
      '#title' => $this->t("Theme"),
      '#type' => 'select',
      '#options' => [
        'sleekTheme' => $this->t('Sleek'),
        'lightTheme' => $this->t('Classic'),
        'darkTheme' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('ed11y_theme'),
    ];

    $form['theme']['no_load'] = [
      '#title' => $this->t("Do not check on pages with these elements"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Used to block checking on site sections or admin pages.<br>E.g. <code><em>.content-type-example, .tabs__link[href=*"edit"]</em></code>.'),
      '#default_value' => $config->get('no_load'),
    ];

    $form['theme']['ignore_all_if_absent'] = [
      '#title' => $this->t("Hide all alerts on pages where these elements are not present"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Used to limit alerting to nodes where the user can edit something. Suggested selectors:<br> <code><em>.contextual-region a[href*="/edit"], .contextual-region a[href*="/manage"]</em></code>.'),
      '#default_value' => $config->get('ignore_all_if_absent'),
    ];

    $form['theme']['custom_tests'] = [
      '#title' => $this->t('Custom tests'),
      '#type' => 'number',
      '#min' => 0,
      '#max' => 999,
      '#description' => $this->t('Set to the number of other themes or modules that will be <a href="https://editoria11y.princeton.edu/configuration/#customtests">injecting custom result JS events</a>.'),
      '#default_value' => (int) $config->get('custom_tests'),
    ];

    $form['theme']['position'] = [
      '#type' => 'details',
      '#title' => $this->t('Positioning'),
    ];
    $form['theme']['position']['hide_edit_links'] = [
      '#title' => $this->t("Don't show edit links on tips in these containers"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Tips show copies of the "Edit" and "Layout" links for nodes, users and taxonomy terms. These links are not helpful on lists of content from remote nodes.<br>Provide a comma-separated list of page sections of where these links should not show, E.g.: <code><em>#sidebar-menu, .news-feed</em></code>.<br>To hide the links <strong>everywhere</strong>, set this field to an asterisk (<code><em>*</em></code>).<br>To modify the links, <a href="https://editoria11y.princeton.edu/configuration/#modify-tips" target="_blank">use the ed11yPop event</a> in your theme JS.'),
      '#default_value' => $config->get('hide_edit_links'),
    ];

    $form['theme']['position']['panel_pin'] = [
      '#title' => $this->t("Pin panel to..."),
      '#type' => 'select',
      '#options' => [
        'right' => $this->t("Right"),
        'left' => $this->t("Left"),
      ],
      '#default_value' => $config->get('panel_pin'),
    ];

    $form['theme']['position']['panel_no_cover'] = [
      '#title' => $this->t("Don't cover these other widgets"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Provide a comma-separated list of selectors for other things that appear in the bottom right of the page. <br>If nothing is set, Editoria11y will automatically slide left to accommodate <code><em>#klaro_toggle_dialog, #klaro-cookie-notice .same-page-preview-dialog.ui-dialog-position-side</em></code>.'),
      '#default_value' => $config->get('panel_no_cover'),
    ];

    $form['theme']['position']['element_hides_overflow'] = [
      '#title' => $this->t("Elements with overflow hidden"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Sometimes buttons get drawn and visually truncated outside the bounds of a positioned element. Provide a selector list.'),
      '#default_value' => $config->get('element_hides_overflow'),
    ];

    $form['theme']['position']['hidden_handlers'] = [
      '#title' => $this->t("Theme JS will handle revealing hidden tooltips inside these containers"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Editoria11y detects hidden tooltips and warns the user when they try to jump to them from the panel. For elements on this list, Editoria11y will <a href="https://itmaybejj.github.io/editoria11y/#dealing-with-alerts-on-hidden-or-size-constrained-content">dispatch a JS event</a> instead of a warning, so custom JS in your theme can first reveal the hidden tip (e.g., open an accordion or tab panel).'), // phpcs:ignore
      '#default_value' => $config->get('hidden_handlers'),
    ];

    $form['theme']['dynamic'] = [
      '#type' => 'details',
      '#title' => $this->t('Detecting dynamic and shadow content'),
    ];

    $form['theme']['dynamic']['watch_for_changes'] = [
      '#title' => $this->t("Dynamically refresh if new content appears"),
      '#type' => 'select',
      '#options' => [
        'true' => $this->t('Watch for changes anywhere on the page'),
        'checkRoots' => $this->t('Only watch for changes to content containers present on load'),
        'false' => $this->t('Do not watch for changes'),
      ],
      '#default_value' => $config->get('watch_for_changes') ?? 'checkRoots',
      '#description' => $this->t('Set to "anywhere" if changes are being missed, set to "ignore" if you notice performance issues. Themes and modules can also call <code>Drupal.Ed11y.refresh()</code> to refresh results.'),
    ];

    $form['theme']['dynamic']['shadow_components'] = [
      '#title' => $this->t("Check inside these specific Web components"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#placeholder' => "",
      '#description' => $this->t("Provide selectors for elements with <a href='https://developer.mozilla.org/en-US/docs/Web/Web_Components'>shadow DOM</a> you want tested. E.g.: <code><em>my-fancy-accordion-widget, my-magical-slideshow</em></code>."),
      '#default_value' => $config->get('shadow_components'),
    ];
    $form['theme']['dynamic']['detect_shadow'] = [
      '#title' => $this->t("Auto-detect any Web components"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('detect_shadow'),
      '#description' => $this->t('This is easier to configure than specifying components, but may slow test runs on very complicated pages.'),
    ];

    $form['theme']['sync'] = [
      '#type' => 'details',
      '#title' => $this->t('Syncing results to reports'),
      '#markup' => '<p>' . $this->t("Remember that results only sync to the dashboard when viewing nodes. Results shown while editing or viewing previews or revisions will not sync.") . '</p>',
    ];
    $form['theme']['sync']['redundant_prefix'] = [
      '#title' => $this->t("Remove redundant base url from URLs"),
      '#type' => 'textfield',
      '#default_value' => $config->get('redundant_prefix'),
      '#description' => $this->t('Provide base URL ("/mysite") if your site is installed in a subdirectory. Subdirectories tend to get duplicated (/mysite/mysite/mypage) and throw errors from the API.'),
    ];
    $form['theme']['sync']['preserve_params'] = [
      '#title' => $this->t("Preserve query parameters"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#placeholder' => 'search,page,keys',
      '#default_value' => $config->get('preserve_params'),
      '#description' => $this->t('The dashboard ignores most parameters: results for both /news?f=1 and /news?f=2 will show up as just /news. Provide a comma separated list of parameters that are meaningful, and should appear as separate pages in results.'),
    ];

    $form['theme']['sync']['disable_sync'] = [
      '#title' => $this->t("Disable sync altogether"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('disable_sync'),
      '#description' => $this->t('Syncing test results back to Drupal is required for the <a target="_blank" href="/admin/reports/editoria11y">issue</a> and <a target="_blank" href="/admin/reports/editoria11y/dismissals">dismissal</a> dashboards and "mark OK" buttons.'),
    ];

    $form['theme']['headings'] = [
      '#type' => 'details',
      '#title' => $this->t('Heading outline position of editable content'),
      '#markup' => '<p>' . $this->t('To check headings in CKEditor, Editoria11y needs to know what the first heading level should be in this field. Body fields should generally be at the h2 level.') . '</p>',
    ];
    $form['theme']['headings']['live_h2'] = [
      '#title' => $this->t("H2 level fields (body content)"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Body fields on nodes are preceded by an h1, and their heading outline should start with an h2. Ideally set this for top-level body fields for each of your content types, and set blocks and embedded nodes to h3 or h4.
        <br>Set all content types: <code><em>form[id^="node-"] #edit-body-wrapper .ck-content</em></code>
        <br>Set specific content types: <code><em>form[id^="node-"] #edit-body-wrapper .ck-content</em></code>
        <br>Set up for Gutenberg: <code><em>form[id^="node-"] #edit-body-wrapper .is-root-container</em></code>'),
      '#default_value' => $config->get('live_h2'),
    ];
    $form['theme']['headings']['live_h3'] = [
      '#title' => $this->t("H3 level fields (blocks or paragraphs with separate titles)"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Sometimes inline and layout builder blocks are grouped under an h2 from a separate field, so their highest heading level should be h3.'),
      '#default_value' => $config->get('live_h3'),
    ];
    $form['theme']['headings']['live_h4'] = [
      '#title' => $this->t("H4 level fields (deeply nested blocks or paragraphs)"),
      '#type' => 'textarea',
      '#rows' => 1,
      '#description' => $this->t('Sometimes inline and layout builder blocks are grouped under an h3 from a separate field, so their highest heading level should be h4.'),
      '#default_value' => $config->get('live_h4'),
    ];

    $form['content_tests'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Modify tests that usually detect content issues'),
    ];

    $form['template_tests'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Modify tests that usually detect template or configuration issues'),
    ];

    // Collect all state needed to render each test row.
    $main_tests_off = empty($config->get('tests_off')) ? [] : explode(',', $config->get('tests_off'));
    $csa_tests_off = ($csa && $csaConfig) ? (empty($csaConfig->get('tests_off')) ? [] : explode(',', $csaConfig->get('tests_off'))) : [];
    $csa_tests_content = ($csa && $csaConfig) ? (empty($csaConfig->get('tests_content')) ? [] : explode(',', $csaConfig->get('tests_content'))) : [];
    $csa_tests_dev = ($csa && $csaConfig) ? (empty($csaConfig->get('tests_dev')) ? [] : explode(',', $csaConfig->get('tests_dev'))) : [];
    $content_tests = TestNames::contentTests();
    $off_by_default = TestNames::offByDefault();
    $core_names = TestNames::coreNames();

    // Build a lookup of which tests belong to each group.
    $grouped = [];
    foreach ($core_names as $key => $label) {
      $grouped[TestNames::groupForKey($key)][$key] = $label;
    }

    // Render groups in the order defined by TestNames::groupLabels().
    foreach (TestNames::groupLabels() as $group_id => $group_label) {

      $set = TestNames::groupSet($group_id);

      $form[$set][$group_id] = [
        '#type' => 'details',
        '#title' => $group_label,
        '#attributes' => ['class' => ['ed11y-test-group']],
      ];

      // Inject group-level text fields first.
      if ($group_id === 'contrast') {
        $form[$set]['contrast']['contrast_ignore'] = [
          '#title' => $this->t("Do not check contrast for these elements"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#description' => $csa
            ? $this->t('Provide a comma-separated list of selectors for elements to ignore for all users.')
            : '<ul>' . $installCSA . '</ul>',
          '#default_value' => $csa && $csaConfig ? $csaConfig->get('contrast_ignore') : '',
          '#disabled' => !$csa,
          '#suffix' => '<hr>',
        ];
      }
      elseif ($group_id === 'embeds') {
        $form[$set]['embeds']['embedded_content_warning'] = [
          '#title' => $this->t("Remind editor that content in these embeds needs manual review"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#description' => $this->t('Provide a comma-separated list of selectors you wish to flag for the editor, e.g.: <code><em>.my-embedded-feed, #my-social-link-block</em></code>.'),
          '#default_value' => $config->get('embedded_content_warning'),
          '#suffix' => '<hr>',
        ];
      }
      elseif ($group_id === 'links_content') {
        $form[$set]['links_content']['#markup'] = $this->t('Default settings should work with both <a href="https://www.drupal.org/project/linkpurpose" target="_blank">Link Purpose Icons</a> and <a target="_blank" href="https://www.drupal.org/project/extlink">External Links</a>.');

        $form[$set]['links_content']['download_links'] = [
          '#title' => $this->t("Remind the editor that these linked documents need a manual check"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#placeholder' => "a[href$='.pdf'], a[href*='.pdf?']",
          '#description' => $this->t("Add or remove filetypes. Set to \"false\" to disable the test altogether. Providing any value will override the default, which is <code><em>a[href$='.pdf'], a[href*='.pdf?']</em></code>."),
          '#default_value' => $config->get('download_links'),
        ];
        $form[$set]['links_content']['link_ignore_selector'] = [
          '#title' => $this->t("Remove elements that match these selectors before testing link text"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#placeholder' => $config->get('link_ignore_selector'),
          '#description' => $this->t('Provide a CSS selector of elements your modules programmatically add to links (usually external or open-in-new-window links), so they can be ignored when the link text is checked for the "link has no text" and "link text is not meaningful" tests.<br>E.g.: <code><em>.this, .that</em></code>'),
          '#default_value' => $config->get('link_ignore_selector'),
        ];
        $form[$set]['links_content']['ignore_link_strings'] = [
          '#title' => $this->t("Remove these strings before testing link text"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#placeholder' => "(link is external)|(link sends email)",
          '#description' => $this->t('Provide a pipe-separated ("|") list of phrases your modules programmatically add to links to hint a purpose (external, mail, phone, open-in-new-window), so they can be ignored when the link text is checked for the "link has no text" and "link text is not meaningful" tests; e.g.:  <pre><code><em>(link is external)|(link sends email)</em></code></pre>'),
          '#default_value' => $config->get('ignore_link_strings'),
        ];
        $form[$set]['links_content']['link_strings_new_windows'] = [
          '#title' => $this->t("Strings in links that indicate new tabs"),
          '#type' => 'textarea',
          '#rows' => 1,
          '#placeholder' => "(download)|(window)|(tab)",
          '#description' => $this->t('Provide a pipe-separated list of phrases your site uses to warn users a link opens in a new tab; e.g.:  <pre><code><em>new&nbsp;tab|new&nbsp;window|external</em></code></pre>'),
          '#default_value' => $config->get('link_strings_new_windows'),
          '#suffix' => '<hr>',
        ];
      }

      // Render each test row in this group, sorted by label for stable output.
      $group_tests = $grouped[$group_id] ?? [];
      asort($group_tests);
      foreach ($group_tests as $key => $label) {
        if (in_array($key, $content_tests, TRUE)) {
          $form[$set][$group_id][$key] = $this->buildTestRow(
          $key,
          $label,
          $csa,
          $content_tests,
          $off_by_default,
          $main_tests_off,
          $csa_tests_off,
          $csa_tests_content,
          $csa_tests_dev,
          );
        }
      }
      foreach ($group_tests as $key => $label) {
        if (!in_array($key, $content_tests, TRUE)) {
          $form[$set][$group_id][$key] = $this->buildTestRow(
          $key,
          $label,
          $csa,
          $content_tests,
          $off_by_default,
          $main_tests_off,
          $csa_tests_off,
          $csa_tests_content,
          $csa_tests_dev,
          );
        }
      }
    }

    return parent::buildForm($form, $form_state);

  }

  /**
   * Builds a single test-row form element.
   *
   * When CSA is active, renders a 3-way select (Off / Developers / All roles)
   * with its default derived from the current test_content/test_dev/test_off
   * CSVs. When CSA is inactive, content tests render as enabled checkboxes
   * where a checked state means the test is on (inverse polarity), and
   * developer-only tests render as disabled "off" checkboxes.
   *
   * @param string $key
   *   The test key (e.g. "HEADING_EMPTY").
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable test label.
   * @param bool $csa
   *   Whether CSA is installed and active.
   * @param string[] $content_tests
   *   List of keys from TestNames::contentTests().
   * @param string[] $off_by_default
   *   List of keys from TestNames::offByDefault().
   * @param string[] $main_tests_off
   *   Current contents of editoria11y.settings:tests_off.
   * @param string[] $csa_tests_off
   *   Current contents of editoria11y_csa.settings:tests_off.
   * @param string[] $csa_tests_content
   *   Current contents of editoria11y_csa.settings:tests_content.
   * @param string[] $csa_tests_dev
   *   Current contents of editoria11y_csa.settings:tests_dev.
   *
   * @return array
   *   A form element render array.
   */
  private function buildTestRow(
    string $key,
    string|TranslatableMarkup $label,
    bool $csa,
    array $content_tests,
    array $off_by_default,
    array $main_tests_off,
    array $csa_tests_off,
    array $csa_tests_content,
    array $csa_tests_dev,
  ): array {
    $is_content = in_array($key, $content_tests, TRUE);
    $is_off = in_array($key, $main_tests_off, TRUE) || in_array($key, $csa_tests_off, TRUE);

    $description = $key;

    if ($key === 'LINK_FILE_EXT') {
      $description .= ' (' . $this->t('Modules like Link Purpose or External Links can mark links automatically.') . ')';
    }

    if (!$csa) {
      // CSA inactive: render as a checkbox with inverted polarity.
      if (!$is_content) {
        // Developer-only tests are not available without CSA.
        return [
          '#type' => 'checkbox',
          '#title' => $label,
          '#default_value' => 0,
          '#disabled' => TRUE,
          '#description' => '<em>' . $this->t('CSA/developer test') . '</em>',
        ];
      }
      // Content test: checked = enabled (on).
      return [
        '#type' => 'checkbox',
        '#title' => $label,
        // Enabled when NOT in main tests_off. New installs default to on unless
        // the test is in the "off by default" list.
        '#default_value' => $is_off ? 0 : (in_array($key, $off_by_default, TRUE) && empty($main_tests_off) ? 0 : 1),
        '#description' => $description,
      ];
    }

    // CSA active: render a 3-way select.
    $options = [
      'test_is_for_nobody' => $this->t('Off'),
      'test_is_for_developers' => $this->t('Show to developers'),
      'test_is_for_everybody' => $this->t('Show to everybody'),
    ];
    // Mark the natural default with a star.
    if (in_array($key, $off_by_default, TRUE)) {
      // Default.
      $options['test_is_for_nobody'] = $this->t('Off');
    }
    elseif ($is_content) {
      // Default.
      $options['test_is_for_everybody'] = $this->t('Show to everybody');
    }
    else {
      // Default.
      $options['test_is_for_developers'] = $this->t('Show to developers');
    }

    // Determine the current default from stored lists (priority: off > content
    // > dev > natural default).
    if ($is_off) {
      $default = 'test_is_for_nobody';
    }
    elseif (in_array($key, $csa_tests_content, TRUE)) {
      $default = 'test_is_for_everybody';
    }
    elseif (in_array($key, $csa_tests_dev, TRUE)) {
      $default = 'test_is_for_developers';
    }
    elseif (in_array($key, $off_by_default, TRUE)) {
      $default = 'test_is_for_nobody';
    }
    elseif ($is_content) {
      $default = 'test_is_for_everybody';
    }
    else {
      $default = 'test_is_for_developers';
    }

    return [
      '#type' => 'select',
      '#title' => $label,
      '#options' => $options,
      '#default_value' => $default,
      '#description' => $key,
      '#wrapper_attributes' => ['class' => ['ed11y-select-grid']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $csa = $this->moduleHandler->moduleExists('editoria11y_csa') &&
      CSAStatus::current($this->state)->isActive();
    $content_tests = TestNames::contentTests();
    $core_names = TestNames::coreNames();

    // Preserve existing values from config, then overlay the submitted state.
    // In CSA-inactive mode we only mutate content-test entries in main
    // tests_off; in CSA-active mode we rebuild all four CSVs from scratch.
    $existing_main_off = $this->config('editoria11y.settings')->get('tests_off');
    $existing_main_off = empty($existing_main_off) ? [] : explode(',', $existing_main_off);

    if ($csa) {
      $main_tests_off = [];
      $csa_tests_off = [];
      $csa_tests_content = [];
      $csa_tests_dev = [];
    }
    else {
      // Keep any non-content entries (developer tests that were disabled while
      // CSA was active) untouched so they come back on reactivation.
      $main_tests_off = array_values(array_filter(
        $existing_main_off,
        fn($k) => !in_array($k, $content_tests, TRUE),
      ));
      $csa_tests_off = NULL;
      $csa_tests_content = NULL;
      $csa_tests_dev = NULL;
    }

    foreach ($core_names as $key => $label) {
      $value = $form_state->getValue($key);

      if (!$csa) {
        // Only content-test checkboxes are editable. Skip dev-only tests.
        if (!in_array($key, $content_tests, TRUE)) {
          continue;
        }
        // Checkbox: 0 = off, 1 = on. "Off" means the key is in tests_off.
        if ((int) $value === 0) {
          $main_tests_off[] = $key;
        }
        continue;
      }

      // CSA active: value is one of three strings.
      $is_content = in_array($key, $content_tests, TRUE);
      if ($value === 'test_is_for_nobody') {
        // Preserve the current storage split: content tests off → main config,
        // developer tests off → CSA config.
        if ($is_content) {
          $main_tests_off[] = $key;
        }
        else {
          $csa_tests_off[] = $key;
        }
      }
      elseif ($value === 'test_is_for_everybody') {
        $csa_tests_content[] = $key;
      }
      elseif ($value === 'test_is_for_developers') {
        $csa_tests_dev[] = $key;
      }
    }

    $this->config('editoria11y.settings')
      ->set('assertiveness', $form_state->getValue('content_assertiveness'))
      ->set('ck5_table_headers', $form_state->getValue('ck5_table_headers'))
      ->set('content_root', $form_state->getValue('content_root'))
      ->set('custom_tests', $form_state->getValue('custom_tests'))
      ->set('detect_shadow', $form_state->getValue('detect_shadow'))
      ->set('disable_live', $form_state->getValue('disable_live'))
      ->set('disable_sync', $form_state->getValue('disable_sync'))
      ->set('download_links', $form_state->getValue('download_links'))
      ->set('ed11y_theme', $form_state->getValue('ed11y_theme'))
      ->set('element_hides_overflow', $form_state->getValue('element_hides_overflow'))
      ->set('embedded_content_warning', $form_state->getValue('embedded_content_warning'))
      ->set('hidden_handlers', $form_state->getValue('hidden_handlers'))
      ->set('hide_edit_links', $form_state->getValue('hide_edit_links'))
      ->set('ignore_all_if_absent', $form_state->getValue('ignore_all_if_absent'))
      ->set('ignore_elements', $form_state->getValue('ignore_elements'))
      ->set('ignore_link_strings', $form_state->getValue('ignore_link_strings'))
      ->set('link_ignore_selector', $form_state->getValue('link_ignore_selector'))
      ->set('link_strings_new_windows', $form_state->getValue('link_strings_new_windows'))
      ->set('live_h2', $form_state->getValue('live_h2'))
      ->set('live_h3', $form_state->getValue('live_h3'))
      ->set('live_h4', $form_state->getValue('live_h4'))
      ->set('no_load', $form_state->getValue('no_load'))
      ->set('panel_no_cover', $form_state->getValue('panel_no_cover'))
      ->set('panel_pin', $form_state->getValue('panel_pin'))
      ->set('preserve_params', $form_state->getValue('preserve_params'))
      ->set('redundant_prefix', $form_state->getValue('redundant_prefix'))
      ->set('shadow_components', $form_state->getValue('shadow_components'))
      ->set('tests_off', implode(',', array_unique($main_tests_off)))
      ->set('watch_for_changes', $form_state->getValue('watch_for_changes'))
      ->save();

    // CSA-bound fields render disabled when CSA is installed-but-inactive,
    // and disabled fields submit their displayed defaults rather than the
    // stored value. Skip writing to the CSA config in that state so we do not
    // clobber stored settings on a no-op save.
    if ($this->moduleHandler->moduleExists('editoria11y_csa') && $csa) {
      $store_roles = [];
      foreach ($form_state->getValue('roles') as $key => $value) {
        if ($value !== 0) {
          $store_roles[] = $key;
        }
      }
      $this->configFactory->getEditable('editoria11y_csa.settings')
        ->set('dev_check_root', $form_state->getValue('dev_check_root') ?? 'automatic')
        ->set('specify_root', $form_state->getValue('specify_root') ?? '.dialog-off-canvas-main-canvas')
        ->set('always_ignore', $form_state->getValue('always_ignore') ?? '')
        ->set('roles', implode(',', $store_roles))
        ->set('assertiveness', $form_state->getValue('dev_assertiveness'))
        ->set('contrast_ignore', $form_state->getValue('contrast_ignore'))
        ->set('tests_off', implode(',', array_unique($csa_tests_off ?? [])))
        ->set('tests_content', implode(',', array_unique($csa_tests_content ?? [])))
        ->set('tests_dev', implode(',', array_unique($csa_tests_dev ?? [])))
        ->save();
    }
    // Increment config version to bust browser cache for the config API.
    $v = $this->state->get('editoria11y.config_version', 0);
    $this->state->set('editoria11y.config_version', $v + 1);
    Cache::invalidateTags(['editoria11y:config']);
    parent::submitForm($form, $form_state);
  }

}
