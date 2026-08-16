/* globals Drupal, drupalSettings, Ed11y, console, ed11yLang, ed11yLangDrupal, editoria11yOptions, drupalTranslations */

/**
 * Drupal initializer.
 * Launch as behavior and pull variables from config.
 */

// Prevent multiple inits.
let ed11yOnce;
let ed11yInitialized;
let ed11yWaiting = false;

const ed11yInitializer = async function () {
  "use strict";

  if (ed11yInitialized === 'disabled' || ed11yInitialized === 'pending') {
    return;
  }
  ed11yInitialized = 'pending';

  const dS = drupalSettings.editoria11y;
  const version = dS.version.replaceAll(/([^\d.])/g, '');

  const urlParams = new URLSearchParams(window.location.search);
  const langs = [
    'en-us',
    'en-gb',
    'en-ca',
    'en',
    'bg',
    'cs',
    'da',
    'de',
    'el',
    'es',
    'et',
    'fi',
    'fr',
    'hu',
    'id',
    'it',
    'ja',
    'ko',
    'lt',
    'lv',
    'nb',
    'nl',
    'pl',
    'pt-br',
    'pt-pt',
    'ro',
    'sk',
    'sl',
    'sv',
    'tr',
    'uk',
    'zh',
  ];
  let lang = dS.lang && langs.includes(dS.lang) ? dS.lang : 'en-us';

  // Loading dynamically with version string.
  const modulePromises = [
    import(`../library/dist/js/ed11y.esm.min.js?v=${version}`),
    import(`./editoria11yOptions.js?v=${version}`),
    import(`./editoria11ySync.js?v=${version}`),
    import(`../library/dist/js/lang/${lang}.js?v=${version}`)
  ];

  const [Ed11y, ed11yOptions, ed11ySync, ed11yLang] = await Promise.all(modulePromises);
  Drupal.ed11yLang = ed11yLang;
  const ed11yLangReady = new Event("ed11yLangReady");
  document.dispatchEvent(ed11yLangReady);

  const noLoad = dS.no_load && document.querySelector(dS.no_load);

  // Way too many race conditions on admin side.
  if (noLoad || document.URL.indexOf('mode=same_page_preview') > -1 ||
    (
      drupalSettings.path.currentPathIsAdmin &&
      dS.disable_live === true && !document.querySelector('#ed11y-demo')
    )
  ) {
    ed11yOnce = true;
    ed11yInitialized = 'disabled';
    if (noLoad) {
      ed11ySync.reportSyncDone();
    }
    return;
  }

  // Fetch global config from cacheable API endpoint.
  // Browser-cached via URL versioning — no network request when cached.
  let globalConfig;
  try {
    const response = await fetch(dS.config_url);
    if (!response.ok) {
      throw new Error(`Config API returned ${response.status}`);
    }
    globalConfig = await response.json();
  } catch (e) {
    console.error('Editoria11y: failed to load config', e);
    ed11yInitialized = false;
    return;
  }

  // Merge: global config as base, drupalSettings page-specific values override.
  const mergedConfig = Object.assign({}, globalConfig, dS);

  if (!!mergedConfig.link_strings_new_windows) {
    const newWindowStrings = mergedConfig.link_strings_new_windows.split('|');
    newWindowStrings.forEach(string => {
      Drupal.ed11yLang.lang.strings.NEW_WINDOW_PHRASES.push(string);
    });
  }

  let options = ed11yOptions.options(lang, mergedConfig, urlParams);

  if (
    document.querySelector(options.preventCheckingIfPresent)
  ) {
    ed11yOnce = true;
    ed11yInitialized = 'disabled';
    return;
  } else if (drupalSettings.path.currentPathIsAdmin && !options.editors &&
    !document.querySelector('#ed11y-demo')) {
    // Ed11y will init later if a behavior brings in something editable.
    ed11yInitialized = false;
    return;
  }

  if (document.querySelector('.layout-builder-form')) {
    // Layout builder is not compatible.
    ed11yOnce = true;
    ed11yInitialized = 'disabled';
    return;
  } else if (options.editors) {
    options = ed11yOptions.editorOptions(options, mergedConfig);
  }

  const editSelector = (selector, action) => {
    return document.querySelector(`:is([id$="-local-tasks"], .block-local-tasks-block, .top-bar__actions) a[href*="/${selector}/"][href$="/${action}"]`);
  };
  const editLink = editSelector('node', 'edit');
  const layoutLink = editSelector('node', 'layout');
  const userLink = editSelector('user', 'edit');
  const termLink = editSelector('taxonomy/term', 'edit');
  const editIcon = document.createElement('span');
  editIcon.classList.add('ed11y-custom-edit-icon');
  editIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M441 59L453 71c9 9 9 25 0 34L424 134 378 88 407 59c9-9 25-9 34 0zM210 256L344 122 390 168 256 302c-3 3-7 5-10 6l-59 17 17-59c1-4 3-8 6-10zM373 25L176 222c-9 9-15 19-18 31l-29 100c-2 8-.1 17 6 24s15 9 24 6l100-27c12-3 23-10 31-18L487 139c28-28 28-74 0-102L475 25C447-3 401-3 373 25zM88 64C39 64 0 103 0 152L0 424c0 49 39 88 88 88l272 0c49 0 88-39 88-88l0-112c0-13-11-24-24-24s-24 11-24 24l0 112c0 22-18 40-40 40L88 464c-22 0-40-18-40-40l0-272c0-22 18-40 40-40l112 0c13 0 24-11 24-24s-11-24-24-24L88 64z"/></svg>';
  const editLinks = document.createElement('div');

  const reLink = function (link, text, wrapper = editLinks, sprite = editIcon) {
    if (!link || !link.href || wrapper.querySelector(`a[href^="${link.href.split('?')[0]}"]`)) { return false; }
    const linkButton = document.createElement('a');
    linkButton.href = link.href;
    linkButton.textContent = text;
    linkButton.prepend(sprite.cloneNode(true));
    if (wrapper.querySelector('a')) {
      wrapper.querySelector('a').insertAdjacentElement('beforebegin', linkButton);
    } else {
      wrapper.prepend(linkButton);
    }
  };
  reLink(layoutLink, Drupal.ed11yLang.lang.strings.edit_layout);
  reLink(editLink, Drupal.ed11yLang.lang.strings.edit_page);
  reLink(userLink, Drupal.ed11yLang.lang.strings.edit_user);
  reLink(termLink, Drupal.ed11yLang.lang.strings.edit_term);

  options.editLinks = !!mergedConfig.hide_edit_links ? false : editLinks;

  document.addEventListener('ed11yPop', e => {
    if (!!mergedConfig.hide_edit_links) {
      if (e.detail.result.element.closest(mergedConfig.hide_edit_links)) {
        e.detail.tip.shadowRoot.querySelector('.ed11y-custom-edit-links')?.setAttribute('hidden', '');
      }
      return;
    }

    // Add contextual edit if available.
    const contextLink = function (selector) {
      return e.detail.result.element?.closest(`.contextual-region.${selector}`)?.querySelector(`:scope > .contextual:first-child a[href*="/${selector}/"][href*="/edit?"]`);
    }
    const tipLinks = e.detail.tip.shadowRoot.querySelector('.ed11y-custom-edit-links');
    if (!tipLinks) {
      return;
    }
    reLink(contextLink('node'), Drupal.Ed11y.Lang.langStrings.edit_page, tipLinks);
    reLink(contextLink('user'), Drupal.Ed11y.Lang.langStrings.edit_user, tipLinks);
    reLink(contextLink('term'), Drupal.Ed11y.Lang.langStrings.edit_term, tipLinks);
    const contextMedia = contextLink('media');
    if (contextMedia) {
      const mediaIcon = editIcon.cloneNode(true);
      mediaIcon.innerHTML = Drupal.Ed11y.sprite.alts;
      reLink(contextMedia, Drupal.Ed11y.Lang.langStrings.edit_media, tipLinks, mediaIcon);
    }

  });

  if (typeof editoria11yOptionsOverride !== 'undefined' && typeof editoria11yOptions === 'function') {
    options = editoria11yOptions(options);
  }

  ed11yWaiting = true;

  window.setTimeout(function () {
    ed11yInitialized = true;

    options.lang = Drupal.ed11yLang.lang;

    // Increase zIndex on tips drawn inside Drupal's modal dialog.
    document.addEventListener('ed11yResultsPainted', function () {
      if (Drupal.Ed11y.UI.inlineAlerts) {
        // Inline alerts inherit z-index.
        return;
      }
      Ed11y.Results?.forEach(result => {
        const inDialog = result?.element?.closest('dialog, [role="dialog"]');
        if (inDialog) {
          result?.toggle?.style.setProperty('--ed11y-buttonZIndex', '99999');
        }
      });
    });

    const ed11y = new Ed11y.Ed11y(options);
    Drupal.Ed11y = Ed11y;

    ed11yWaiting = false;

    if (options.editors) {
      document.addEventListener('click', function (e) {
        if (e.target.closest('.ck-toolbar')) {
          window.setTimeout(function () {
            Drupal.Ed11y.UI.interaction = true;
            Drupal.Ed11y.refresh();
          }, 1000);
        }
      });

      // @todo: this gives us canonical change events.
      if (Drupal.CKEditor5Instances && Drupal.CKEditor5Instances.size > 0) {
        Drupal.CKEditor5Instances.forEach((editor) => {
          /*editor.model.document.on('change', (e) => {
            // Check if the differ has any changes to filter out selection-only changes
            console.log('change', e);

          });*/
          /*editor.model.document.on('change:data', (evt) => {
            // Check if the differ has any changes to filter out selection-only changes
            const differ = editor.model.document.differ;
            if (!differ.isEmpty) {
              console.log('The editor data has changed!');
            }
          })*/
        })
      }
    }

    // When Drupal dialog opens, constrain checks inside dialog.
    Drupal.Ed11y.State.rootsCache = false;
    const watchable = '#drupal-modal, .ui-dialog.ui-front';
    let dialogRoots = '';
    document.addEventListener('dialog:afterclose', function () {
      if (Drupal.Ed11y.State.option.preventCheckingIfPresent &&
        Drupal.Ed11y.State.option.preventCheckingIfPresent.includes(watchable)) {
        Drupal.Ed11y.State.option.preventCheckingIfPresent =
          Drupal.Ed11y.State.option.preventCheckingIfPresent.replaceAll(', ' + watchable, '');
        Drupal.Ed11y.State.option.preventCheckingIfPresent =
          Drupal.Ed11y.State.option.preventCheckingIfPresent.replaceAll(watchable, '');
        window.setTimeout(function () {
          Drupal.Ed11y.refresh();
          Drupal.Ed11y.UI.panelElement.zIndex = 'calc(var(--ed11y-buttonZIndex,9999) + 10000)';
        }, 100);
      }

      // todo check if there are ANY dialogs still open.
      if (Drupal.Ed11y.State.rootsCache) {
        Drupal.Ed11y.State.option.checkRoot = Drupal.Ed11y.State.rootsCache;
        Drupal.Ed11y.State.rootsCache = false;
        window.setTimeout(function () {
          Drupal.Ed11y.refresh();
        }, 1000);
      }
    });
    document.addEventListener('dialog:aftercreate', function () {
      const modal = document.querySelector(watchable);
      const watched = modal?.querySelector(Drupal.Ed11y.State.option.checkRoot);
      if (!watched) {
        Drupal.Ed11y.UI.panelElement.style.zIndex = 1;
        Drupal.Ed11y.State.option.preventCheckingIfPresent =
          Drupal.Ed11y.State.option.preventCheckingIfPresent &&
            Drupal.Ed11y.State.option.preventCheckingIfPresent.includes(watchable) ?
            Drupal.Ed11y.State.option.preventCheckingIfPresent + ', ' + watched :
            watchable;
        return;
      }
      // @todo 3.x test conversion from fixedRoots while running.
      if (!Drupal.Ed11y.State.rootsCache) {
        Drupal.Ed11y.State.rootsCache = Drupal.Ed11y.State.option.checkRoot;
        const rootsParse = Drupal.Ed11y.State.rootsCache.split(',');
        rootsParse.forEach((root, i) => {
          rootsParse[i] = `#drupal-modal ${root}`;
        });
        dialogRoots = rootsParse.join(', ');
      }
      Drupal.Ed11y.State.option.checkRoot = dialogRoots;
      window.setTimeout(function () {
        Drupal.Ed11y.refresh();
      }, 1000); // todo merge
      // Todo: if Editoria11y disables, drop its zIndex behind the modal?
    });

    const urlParams = new URLSearchParams(window.location.search);

    window.setTimeout(function () {
      if (Drupal.Ed11y.UI.disabled) {
        ed11ySync.reportSyncDone();
        // Tell crawler to move on.
      }
    }, 100);
  }, options.delay);

  // Initiate sync.
  ed11ySync.sync(options, urlParams);

  ed11yOnce = true;
};

Drupal.behaviors.editoria11y = {
  attach: function (context) {
    "use strict";



    if (ed11yInitialized === true && ed11yOnce) {
      // Recheck page about a second after every behavior.
      // Todo: global mutation watch instead or in addition?

      if (Drupal.Ed11y.State.rootsCache && !document.querySelector('#drupal-modal')) {
        // Failure state if a dialog close is missed.
        Drupal.Ed11y.State.option.checkRoot = Drupal.Ed11y.State.rootsCache;
        Drupal.Ed11y.State.rootsCache = false;
      }
      window.setTimeout(function () {
        // Force a full refresh after each Drupal behavior.
        Drupal.Ed11y.UI.forceFullCheck = true;
        if (drupalSettings.editor || typeof (DrupalGutenberg) === 'object') {
          Drupal.Ed11y.UI.inlineAlerts = false;
        }
        if (!Drupal.Ed11y.UI.running) {
          Drupal.Ed11y.refresh();
        }
      }, 1000);
    } else if (ed11yOnce &&
      (!ed11yInitialized ||
        ed11yInitialized !== 'pending'
      ) &&
      !drupalSettings.editoria11y.disable_live &&
      Drupal.editors &&
      (Object.hasOwn(Drupal.editors, 'ckeditor5') ||
        Object.hasOwn(Drupal.editors, 'gutenberg'))) {
      window.setTimeout(function () {
        if (ed11yInitialized !== true) {
          ed11yInitializer().then();
        }
      }, 1000);

    }

    if (context === document && !ed11yOnce &&
      CSS.supports('selector(:is(body))')) {
      ed11yOnce = true;
      // Timeout necessary to prevent Paragraphs needing 2 clicks to open.
      window.setTimeout(() => {
        ed11yInitializer().then();
      }, 100);
    }
  }
};

// Observe for late-loaded media while editing.
// @todo move to an editor-only module.
(function (Drupal) {
  Drupal.behaviors.mediaEmbedImageLoaded = {
    attach(context) {
      // Use `once` to avoid re-attaching on every Ajax rebuild
      context.querySelectorAll('.field--type-text-long:not(.ed11yLoadWatch)').forEach((editable) => {
        editable.classList.add('ed11yLoadWatch');
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
              if (!(node instanceof HTMLElement)) return;

              // Grab any <img> tags added in this mutation
              const imgS = [
                ...(node.matches('img') ? [node] : []),
                ...Array.from(node.querySelectorAll('img')),
              ];

              imgS?.forEach((img) => {
                if (img.complete) {
                  // Already cached/loaded
                  onMediaImageLoaded(img);
                } else {
                  img.addEventListener('load', () => onMediaImageLoaded(img), { once: true });
                }
              });
            });
          });
        });

        observer.observe(editable, {
          childList: true,
          subtree: true,
        });
      });
    },
  };

  function onMediaImageLoaded() {
    if (Drupal.Ed11y) {
      Drupal.Ed11y.UI.forceFullCheck = true;
      Drupal.Ed11y.refresh();
    }
    // Your logic here
  }
})(Drupal);
