<?php

namespace Drupal\editoria11y\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class to define all settings of the module.
 */
class Editoria11ySettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editoria11y_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'editoria11y.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('editoria11y.settings');
    $permissions = Url::fromRoute('user.admin_permissions');
    $linkToPermissions = Link::fromTextAndUrl(t("user roles that edit content"), $permissions)->toString();
    $dashboard = Url::fromRoute('editoria11y.reports_dashboard');
    $linkToDashboard = Link::fromTextAndUrl(t("view the dashboard"), $dashboard)->toString();

    $form['text'] = [
      '#markup' => '<h2>Getting started</h2><ol><li>Make sure ' . $linkToPermissions . ' have the "View Editoria11y checker" permission, and assign permissions to "mark OK" as appropriate.</li><li>To ' . $linkToDashboard . ', users need "Manage Editoria11y results." For the link to appear in their admin toolbar, they would also need "View site reports."</li><li>If the checker <strong>toggle</strong> does not appear: make sure a z-indexed or overflow-hidden element in your front-end theme is not hiding or covering the <code><em>ed11y-element-panel</em></code> container, make sure that any custom selectors in the "Disable the scanner if these elements are detected" field are not present, and make sure that no JavaScript errors are appearing in your <a href="https://developer.mozilla.org/en-US/docs/Tools/Browser_Console">browser console</a>.</li><li>If the checker toggle is <strong>present</strong> but never reporting errors or missing items that should be flagged: check that your inclusions & exclusion settings below are not missing or ignoring the elements. It is not uncommon for homepages or views to accidentally insert editable content outside the <code>main</code> element.</li></ol><p><a href="https://www.drupal.org/project/editoria11y">Project overview</a> | <a href="https://jjameson.mycpanel.princeton.edu/editoria11y/">Working Demo</a> | <a href="https://www.drupal.org/project/issues/editoria11y?categories=All">Issue queue</a></p>',
    ];

    $form['setup'] = [
      '#type' => 'fieldset',
      '#title' => t('Basic Configuration'),
    ];

    $form['setup']['ed11y_theme'] = [
      '#title' => $this->t("Theme"),
      '#type' => 'select',
      '#options' => [
        'sleekTheme' => $this->t('Sleek'),
        'lightTheme' => $this->t('Classic'),
        'darkTheme' => $this->t('Dark'),
      ],
      '#default_value' => $config->get('ed11y_theme'),
    ];

    $form['setup']['content_root'] = [
      '#title' => $this->t("Check content in these containers"),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#description' => $this->t('To limit checks to user-editable containers, provide a list of <a target="_blank" href="https://developer.mozilla.org/en-US/docs/Learn/CSS/Building_blocks/Selectors">CSS selectors</a>.<br>E.g.: <code><em>main, #footer-editable-content</em></code><br>Default: <code>main</code>, falling back to <code>body</code>. If elements are specified here and none are found on the page, Editoria11y will not find anything!'),
      '#default_value' => $config->get('content_root'),
    ];

    $form['setup']['ignore_elements'] = [
      '#title' => $this->t("Skip over these elements"),
      '#type' => 'textarea',
      '#placeholder' => '',
      '#description' => $this->t('Provide a comma-separated list of selectors for elements to ignore. These should target specific elements (use an asterisk to indicate "all children of this element"). <br>E.g.: <code><em>#sidebar-menu a, .card img, .slide [aria-hidden="true"], .feed *</em></code>.'),
      '#default_value' => $config->get('ignore_elements'),
    ];

    $form['subhead2'] = [
      '#markup' => '<h2>Advanced configuration</h2>',
    ];
    $form['tests'] = [
      '#type' => 'details',
      '#title' => t('Customize tests'),
    ];

    $form['tests']['ignore_link_strings'] = [
      '#title' => $this->t("Remove these strings before testing link text"),
      '#type' => 'textfield',
      '#placeholder' => "\(link is external\)|\(link sends email\)",
      '#description' => $this->t('Provide a Regex of strings your modules programmatically add to links (usually external or open-in-new-window links), so they can be ignored when the link text is checked for the "link has no text" and "link text is not meaningful" tests. Escape characters as needed to form a valid regex; e.g.: <br><code><em>\(link is external\)|\(link sends email\)|\(download\)</em></code>'),
      '#default_value' => $config->get('ignore_link_strings'),
    ];
    $form['tests']['embedded_content_warning'] = [
      '#title' => $this->t("Remind editor that content in these embeds needs manual review"),
      '#type' => 'textfield',
      '#description' => $this->t('Provide a comma-separated list of selectors you wish to flag for the editor, e.g.: <code><em>.my-embedded-feed, #my-social-link-block</em></code>.'),
      '#default_value' => $config->get('embedded_content_warning'),
    ];
    $form['tests']['download_links'] = [
      '#title' => $this->t("Remind the editor that these linked documents need a manual check"),
      '#type' => 'textarea',
      '#placeholder' => "a[href$='.pdf'], a[href*='.pdf?'], a[href$='.doc'], a[href$='.docx'], a[href*='.doc?'], a[href*='.docx?'], a[href$='.ppt'], a[href$='.pptx'], a[href*='.ppt?'], a[href*='.pptx?'], a[href^='https://docs.google']",
      '#description' => $this->t("Add or remove filetypes. Set to \"false\" to disable the test altogether. Providing any value will override the default, which is: <br><code><em>a[href$='.pdf'], a[href*='.pdf?'], a[href$='.doc'], a[href$='.docx'], a[href*='.doc?'], a[href*='.docx?'], a[href$='.ppt'], a[href$='.pptx'], a[href*='.ppt?'], a[href*='.pptx?'], a[href^='https://docs.google']</em></code>."),
      '#default_value' => $config->get('download_links'),
    ];
    $form['tests']['shadow_components'] = [
      '#title' => $this->t("Scan inside these Web components"),
      '#type' => 'textarea',
      '#placeholder' => "",
      '#description' => $this->t("Provide selectors <a href='https://developer.mozilla.org/en-US/docs/Web/Web_Components'>shadow hosts</a> with editable content. E.g.: <code><em>my-fancy-accordion-widget, my-magical-slideshow</em></code>."),
      '#default_value' => $config->get('shadow_components'),
    ];

    $form['results'] = [
      '#type' => 'details',
      '#title' => t('Customize when and how results appear'),
    ];
    $form['results']['assertiveness'] = [
      '#title' => $this->t("Open the issue details panel automatically when new issues are detected"),
      '#type' => 'radios',
      '#options' => [
        'smart' => $this->t('When nodes are created or changed'),
        'assertive' => $this->t('Always'),
        'polite' => $this->t('Never'),
      ],
      '#description' => $this->t('"Always" is not recommended for sites with multiple editors.'),
      '#default_value' => $config->get('assertiveness'),
    ];
    $form['results']['no_load'] = [
      '#title' => $this->t("Disable the scanner if these elements are detected"),
      '#type' => 'textfield',
      '#placeholder' => '#quickedit-entity-toolbar, .layout-builder-form',
      '#description' => $this->t('Provide a comma-separated list of selectors that disable the scanner when present; e.g during inline editing or a view page with no user-editable content  (<code><em>#inline-editor-open</em></code>) or on pages without user-editable content (<code><em>.node-261, .front</em></code>).'),
      '#default_value' => $config->get('no_load'),
    ];
    $form['results']['ignore_all_if_absent'] = [
      '#title' => $this->t("Hide all alerts if none of these elements are present"),
      '#type' => 'textfield',
      '#placeholder' => '',
      '#description' => $this->t('Used to limit toggle to nodes where the user can edit something. Suggested selectors: (<code><em>.contextual-region a[href*="/edit"], .contextual-region a[href*="/manage"]</em></code>).'),
      '#default_value' => $config->get('ignore_all_if_absent'),
    ];
    $form['results']['hidden_handlers'] = [
      '#title' => $this->t("Theme JS will handle revealing hidden tooltips inside these containers"),
      '#type' => 'textfield',
      '#description' => $this->t('Editoria11y detects hidden tooltips and warns the user when they try to jump to them from the panel. For elements on this list, Editoria11y will <a href="https://itmaybejj.github.io/editoria11y/#dealing-with-alerts-on-hidden-or-size-constrained-content">dispatch a JS event</a> instead of a warning, so custom JS in your theme can first reveal the hidden tip (e.g., open an accordion or tab panel).'),
      '#default_value' => $config->get('hidden_handlers'),
    ];
    $form['sync'] = [
      '#type' => 'details',
      '#title' => t('Customize results dashboard'),
    ];
    $form['sync']['preserve_params'] = [
      '#title' => $this->t("Preserve query parameters"),
      '#type' => 'textfield',
      '#placeholder' => 'search,page,keys',
      '#default_value' => $config->get('preserve_params'),
      '#description' => $this->t('The dashboard ignores most parameters: results for both /news?f=1 and /news?f=2 will show up as just /news. Provide a comma separated list of parameters that are meaningful, and should appear as separate pages in results.'),
    ];
    $form['sync']['disable_sync'] = [
      '#title' => $this->t("Disable sync altogether"),
      '#type' => 'checkbox',
      '#default_value' => $config->get('disable_sync'),
      '#description' => $this->t('Syncing test results back to Drupal is required for the <a target="_blank" href="/admin/reports/editoria11y">issue</a> and <a target="_blank" href="/admin/reports/editoria11y/dismissals">dismissal</a> dashboards and "mark OK" buttons.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('editoria11y.settings')
      ->set('ignore_elements', $form_state->getValue('ignore_elements'))
      ->set('assertiveness', $form_state->getValue('assertiveness'))
      ->set('no_load', $form_state->getValue('no_load'))
      ->set('disable_sync', $form_state->getValue('disable_sync'))
      ->set('ed11y_theme', $form_state->getValue('ed11y_theme'))
      ->set('ignore_all_if_absent', $form_state->getValue('ignore_all_if_absent'))
      ->set('content_root', $form_state->getValue('content_root'))
      ->set('shadow_components', $form_state->getValue('shadow_components'))
      ->set('download_links', $form_state->getValue('download_links'))
      ->set('embedded_content_warning', $form_state->getValue('embedded_content_warning'))
      ->set('hidden_handlers', $form_state->getValue('hidden_handlers'))
      ->set('ignore_link_strings', $form_state->getValue('ignore_link_strings'))
      ->set('preserve_params', $form_state->getValue('preserve_params'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
