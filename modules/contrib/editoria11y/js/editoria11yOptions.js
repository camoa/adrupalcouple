export function options(lang, dS, urlParams) {
  "use strict";

  let alwaysIgnore = '[id^=toolbar-administration], [data-drupal-admin-styles], #admin-toolbar, .tabledrag, .shortcut-action__message, [data-drupal-messages]';
  const insertSelectors = function (opts, base) {
    return !!opts ? `${opts}, ${base}` : base;
  };

  let mergedDismissals = dS.dismissals || {};

  if (typeof dS.dismissals === 'object' && typeof dS.globalDismissals === 'object') {
    // @todo 3.x test if sync is disabled.
    for (const [key, value] of Object.entries(dS.globalDismissals)) {
      // TEST_NAME, {dismissalID = 'okAll'}
      Object.keys(value).forEach((subKey) => {
        if (!mergedDismissals[key]) {
          mergedDismissals[key] = value;
        } else {
          mergedDismissals[key][subKey] = 'okAll';
        }
      });

    }
  }

  let options = {

    embeddedContentPlugin: true,
    linksAdvancedPlugin: true,
    ignoreByTest: {
      // Webform select all.
      LABELS_ARIA_LABEL_INPUT: 'th input[title], :is(header, nav, [data-drupal-selector=search-block-form]) [type="search"]',
      // @todo update treeWalker.
      TABLES_EMPTY_HEADING: 'th.select-all:has(input[title])',
      // @todo fix this text.
      LABEL_IN_NAME: 'a.ext, a.link-purpose',
      LABELS_PLACEHOLDER: ':is(header, nav, [data-drupal-selector=search-block-form]) [type="search"]',
    },
    /* End alpha-only check configuration */
    checkRoot: !!dS.content_root ? dS.content_root : 'main',
    containerIgnore: insertSelectors(dS.ignore_elements, alwaysIgnore),
    paragraphIgnore: 'td > p, th > p',
    ignoreContentOutsideRoots: true,
    panelNoCover: insertSelectors(dS.panel_no_cover, '#klaro-cookie-notice, #klaro_toggle_dialog, .same-page-preview-dialog.ui-dialog-position-side, #gin_sidebar, #admin-toolbar'),
    panelPosition: dS.panel_pin === 'left' ? 'left' : 'right',
    ignoreAllIfAbsent: !!dS.ignore_all_if_absent ? dS.ignore_all_if_absent : false,
    // 100 under contextuals, 491 Gin tools, 1000 CKEditor tools, 1260 modals.
    // Ed11y adds 10000 to tips, 99999 to modal tips. Was 491 until May 2025.
    buttonZIndex: 100,
    autoDetectShadowComponents: !!dS.detect_shadow,
    shadowComponents: dS.shadow_components ? dS.shadow_components : '',
    linkIgnore:
      '[aria-hidden="true"][tabindex="-1"], [id$="-local-tasks"] a, .contextual-links a, .block-local-tasks-block a, .filter-help > a, .contextual-region > nav a',
    // ${drupalSettings.path.currentPathIsAdmin ? ', a[target="_blank"]': ''}
    headerIgnore:
      '.filter-guidelines-item *, nav *, [id$="-local-tasks"] *, ' +
      '.block-local-tasks-block *, .tabledrag h4',
    imageIgnore:
      '[aria-hidden="true"], [aria-hidden="true"] img, [role="presentation"], a[href][aria-label] img, a.ext svg.ext, button[aria-label] img, a[href][aria-labelledby] img, button[aria-labelledby] img',
    lang: lang, // Todo 3.x needed? enUS?
    currentPage: dS.page_path,
    allowHide: !!dS.allow_hide,
    allowOK: !!dS.allow_ok,
    syncedDismissals: mergedDismissals,
    pepper: dS.pepper,
    showDismissed: urlParams.has('ed1ref'),
    linkIgnoreSpan: insertSelectors(dS.link_ignore_selector, '.ed11y-element'),
    hiddenHandlers: !!dS.hidden_handlers ? dS.hidden_handlers : '',
    constrainButtons: insertSelectors(dS.element_hides_overflow, '.js-form-item, .dialog-off-canvas-main-canvas'),
    theme: !!dS.ed11y_theme ? dS.ed11y_theme : 'sleekTheme',
    documentLinks: !!dS.download_links ? dS.download_links : `a[href$='.pdf'], a[href*='.pdf?']`,
    customTests: dS.custom_tests,
    customRules: dS.custom_rules || [],
    cssUrls: !!dS.css_url ? [dS.css_url + '/library/dist/css/editoria11y.min.css'] : false,
    reportsURL: !!dS.view_reports ? dS.dashboard_url : false,
    checks: {
      // Test default overrides.
      EMBED_CUSTOM: !dS.embedded_content_warning ? false : {
        sources: dS.embedded_content_warning,
      },
    },
  };

  if (dS.ignore_tests) {
    dS.ignore_tests.forEach((test) => {
      options.checks[test] = false;
    });
  }

  options.linkIgnoreStrings = !!dS.ignore_link_strings ?
    dS.link_strings_new_windows.split('|') :
    [Drupal.t('link is external'), Drupal.t('link sends email')];
  // @todo 3.x update help text it's not a regex any more
  // todo postpone: store dismissalKeys for PDFs in page results, and check dismissals table for page level matches on load.

  options.editors = (Drupal.editors && (Object.hasOwn(Drupal.editors, 'ckeditor5') || Object.hasOwn(Drupal.editors, 'gutenberg')));
  // As of 2.2.10, ignore front-end editors (rich text comment fields).
  if (options.editors) {
    options.inlineAlerts = false;
    const editRoutes = /(node|term|user)\/\d+\/edit/;
    // @todo: does this need to be a parameter?
    if (!drupalSettings.path.currentPathIsAdmin &&
      !drupalSettings.path.currentPath.match(editRoutes)) {
      options.editors = false;
    }
    options.watchForChanges = true;
    options.checks.HEADING_MISSING_ONE = false;
    if (Object.hasOwn(Drupal.editors, 'gutenberg')) {
      options.buttonZIndex = 1000;
      options.constrainButtons += ', .editor-styles-wrapper';
      options.containerIgnore += ', .block-list-appender';
      alwaysIgnore += ', .block-list-appender *';
    } else {
      // CKEditor injects a label that messes up the "text + alt" link test.
      options.ignoreAriaOnElements = '[data-drupal-media-preview], [data-drupal-entity-preview]';
    }
    if (dS.ext_link_modules) {
      // Don't check while editing; they tag rendered links.
      options.checks.LINK_FILE_EXT = false;
      options.checks.LINK_NEW_TAB = false;
    }
  } else {
    if (options.watchForChanges === null) {
      options.watchForChanges = 'checkRoots';
    }
    options.watchForChanges = dS.watch_for_changes === 'checkRoots' ?
      'checkRoots' :
      dS.watch_for_changes !== 'false';
  }

  if (!!dS.profile) {
    // Todo move to separate JS.
    options.contrastPlugin = true;
    options.contrastAlgorithm = 'AA';
    options.readabilityPlugin = true;
    options.developerPlugin = true;
    options.splitConfiguration = {
      active: true,
      showDev: dS.profile === 'dev',
      devChecks: [],
      devOptions: {
        containerIgnore: insertSelectors(dS.sync_ignore, alwaysIgnore),
      },
    };

    if (!options.editors) {
      options.contrastIgnore = dS.contrast_ignore;
      options.splitConfiguration.devOptions.checkRoot = dS.sync_root;
    } else {
      // @todo what if the editors lazy load?
      options.contrastIgnore = insertSelectors(dS.contrast_ignore, '.ck-editor__nested-editable_focused, table .ck-editor__editable a, .ck-link_selected');
      options.splitConfiguration.devOptions.containerIgnore += ', .ck-widget__type-around__button';
    }


    //ck-editor__nested-editable_focused

    // Map tests to dev or content.
    const devChecks = {
      HEADING_MISSING_ONE: !options.editors ? {
        type: 'warning',
      } : false,
      HEADING_FIRST: !options.editors ? {
        type: 'warning',
      } : false,
      IMAGE_DECORATIVE_CAROUSEL: true, // csa
      IMAGE_FIGURE_DECORATIVE: true,
      QA_NESTED_COMPONENTS: {
        sources: '',
      },
      DUPLICATE_TITLE: {
        dismissAll: true,
      },
      LINK_EMPTY_LABELLEDBY: true,
      LINK_STOPWORD_ARIA: true,
      LINK_IDENTICAL_NAME: {
        dismissAll: true,
      },
      LABELS_MISSING_IMAGE_INPUT: true,
      LABELS_INPUT_RESET: true,
      LABELS_MISSING_LABEL: true,
      LABELS_ARIA_LABEL_INPUT: true,
      LINK_MAYBE_BUTTON: {
        type: 'warning',
      },
      LABELS_NO_FOR_ATTRIBUTE: true,
      LABELS_PLACEHOLDER: true,
      EMBED_UNFOCUSABLE: true,
      QA_SMALL_TEXT: true,
      // Meta checks
      META_LANG: !options.editors,
      META_SCALABLE: !options.editors,
      META_MAX: !options.editors,
      META_REFRESH: !options.editors,
      META_TITLE: !options.editors,
      // Developer checks
      DUPLICATE_ID: true,
      UNCONTAINED_LI: true,
      TABINDEX_ATTR: true,
      HIDDEN_FOCUSABLE: true,
      LABEL_IN_NAME: true,
      BTN_EMPTY: true,
      BTN_EMPTY_LABELLEDBY: true,
      BTN_ROLE_IN_NAME: true,

      // Contrast checks
      CONTRAST_WARNING: {
        dismissAll: true,
      },
      CONTRAST_INPUT: true,
      CONTRAST_ERROR: true,
      CONTRAST_PLACEHOLDER: true,
      CONTRAST_PLACEHOLDER_UNSUPPORTED: true,
      CONTRAST_ERROR_GRAPHIC: { type: 'warning' },
      CONTRAST_WARNING_GRAPHIC: false,
      CONTRAST_UNSUPPORTED: {
        dismissAll: true,
      },
    };
    if (dS.tests_off) {
      dS.tests_off.split(',').forEach(test => {
        // @todo 3.x validate selector?
        devChecks[test] = false;
        // Also disable in the base checks in case the test was hard-coded
        // as a content check above.
        if (test in options.checks) {
          options.checks[test] = false;
        }
      });
    }
    const extraContentTests = !!dS.tests_content ? dS.tests_content.split(',') : [];
    const extraDevTests = !!dS.tests_dev ? dS.tests_dev.split(',') : [];
    Object.assign(options.checks, devChecks);
    for (const [key, value] of Object.entries(devChecks)) {
      // Tests explicitly promoted to "all roles" must NOT be pushed onto the
      // dev-only visibility list. (Prior to the fix, `key in extraContentTests`
      // tested numeric array indices and was always false, so promoted tests
      // were never actually shown to content editors.)
      if (!extraContentTests.includes(key)) {
        options.splitConfiguration.devChecks.push(key);
      }
    }
    // Allow content-category tests (which may only live in the hard-coded
    // base `options.checks` above, not in `devChecks`) to be restricted to
    // developers. Any test in tests_dev is added to the dev-only list.
    extraDevTests.forEach(test => {
      if (!options.splitConfiguration.devChecks.includes(test)) {
        options.splitConfiguration.devChecks.push(test);
      }
    });

  }

  // todo postpone: ignoreAllIfPresent
  options.preventCheckingIfPresent = insertSelectors(dS.no_load,
    '.layout-builder-form');

  options.delay = drupalSettings.path.currentPathIsAdmin ? 250 : 0;

  // Use dev_assertiveness for developer profile if available (from CSA).
  const assertiveness = (dS.profile === 'dev' && dS.dev_assertiveness)
    ? dS.dev_assertiveness : dS.assertiveness;
  options.alertMode = assertiveness ? assertiveness : 'assertive';
  // If assertiveness is "smart" we set it to assertive if the doc was recently changed.
  const now = new Date();
  if (drupalSettings.path.currentPathIsAdmin && (Drupal.editors && (Object.hasOwn(Drupal.editors, 'ckeditor5') || Object.hasOwn(Drupal.editors, 'gutenberg'))) && (options.alertMode === 'smart' || options.alertMode === 'assertive')) {
    options.alertMode = 'active';
  }
  else if (
    urlParams.has('ed1ref') ||
    (options.alertMode === 'smart' &&
      ((now / 1000) - dS.changed < 60)
    )
  ) {
    options.alertMode = 'assertive';
  }

  // Only probe the parent frame if we are actually embedded in one.
  // Accessing any property of a cross-origin parent (e.g. Commerce Authnet's
  // AcceptJS iframe, Stripe, YouTube) throws SecurityError even through
  // optional chaining, so we must guard with self !== top and swallow the
  // cross-origin error silently — a cross-origin parent is by definition not
  // Drupal Canvas.
  if (window.self !== window.top) {
    try {
      if (!!(parent?.drupalSettings?.canvas) && !parent.document.body.querySelector('[class^=_PagePreviewIframe]')) {
        // Only run when Drupal Canvas is running if it is in Preview mode.
        options.preventCheckingIfPresent = 'body';
      }
    } catch (error) {
      // Cross-origin parent — not Canvas, nothing to do.
      if (error.name !== 'SecurityError') {
        console.error(error);
      }
    }
  }

  return options;

}

export function editorOptions(options, dS) {
  "use strict";

  // Editable content is present, optimize for speed.
  options.autoDetectShadowComponents = false;
  options.ignoreContentOutsideRoots = true; // @todo 3.x is there also config for this?

  if (Object.hasOwn(Drupal.editors, 'gutenberg')) {
    options.ignoreAriaOnElements = 'h1,h2,h3,h4,h5,h6';
    options.delay = 1000;
    window.setTimeout(function () {
      if (Drupal.Ed11y.State.results.length === 0 && !Drupal.Ed11y.State.running) {
        // Ed11y fails to initialize if Gutenberg is really late.
        Drupal.Ed11y.refresh();
      }
    }, 6000);
  }
  options.checkRoot = '.gutenberg__editor .is-root-container, [contenteditable="true"]:not(.gutenberg__editor [contenteditable], [contenteditable="true"] [contenteditable], .ck-hidden)';
  options.containerIgnore += ', [hidden], [style*="display: none"], [style*="display:none"], [hidden] *, [style*="display: none"] *, [style*="display:none"] *, [data-drupal-message-type]';
  // todo merge
  options.ignoreAllIfAbsent = options.ignoreAllIfAbsent ?
    options.ignoreAllIfAbsent + ', [contenteditable="true"], .gutenberg__editor .is-root-container' :
    '[contenteditable="true"], .gutenberg__editor .is-root-container';

  options.initialHeadingLevel = [];
  if (dS.live_h2) {
    options.initialHeadingLevel.push(
      {
        selector: `:is(${dS.live_h2})`,
        previousHeading: 1,
      }
    );
  }
  if (dS.live_h3) {
    options.initialHeadingLevel.push(
      {
        selector: `:is(${dS.live_h3})`,
        previousHeading: 2,
      }
    );
  }
  if (dS.live_h4) {
    options.initialHeadingLevel.push(
      {
        selector: `:is(${dS.live_h4})`,
        previousHeading: 3,
      }
    );
  }

  return options;
}
