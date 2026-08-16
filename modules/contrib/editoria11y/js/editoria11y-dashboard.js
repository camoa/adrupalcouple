/* globals Drupal, drupalSettings, Ed11y, console */
/**
 * Drupal initializer.
 * Launch as behavior and pull variables from config.
 */

Drupal.behaviors.editoria11yAdmin = {
  attach: function (context, settings) {
    'use strict';

    // Drupal.t does not detect variables so we have to preload.
    // Translation Bliss module would fix all this.
    const translatables = {
      '2-letter code (en, fr, de)': Drupal.t('2-letter code (en, fr, de)'),
      'Alert': Drupal.t('Alert'),
      'Alert types': Drupal.t('Alert types'),
      'Alerts': Drupal.t('Alerts'),
      'All dismissals': Drupal.t('All dismissals'),
      'All issues': Drupal.t('All issues'),
      'All pages': Drupal.t('All pages'),
      'Apply': Drupal.t('Apply'),
      'Author': Drupal.t('Author'),
      'Authored after': Drupal.t('Authored after'),
      'Authored before': Drupal.t('Authored before'),
      'Authored': Drupal.t('Authored'),
      'Content alerts': Drupal.t('Content alerts'),
      'Content type': Drupal.t('Content type'),
      'Dashboard': Drupal.t('Dashboard'),
      'Detected': Drupal.t('Detected'),
      'Dev alerts': Drupal.t('Dev alerts'),
      'Dismissals': Drupal.t('Dismissals'),
      'Dismissed by': Drupal.t('Dismissed by'),
      'Dismissed accessibility alerts': Drupal.t('Dismissed accessibility alerts'),
      'Edited': Drupal.t('Edited'),
      'Editor': Drupal.t('Editor'),
      'Entity type': Drupal.t('Entity type'),
      'Items per page': Drupal.t('Items per page'),
      'Filter': Drupal.t('Filter'),
      'Language': Drupal.t('Language'),
      'Last editor': Drupal.t('Last editor'),
      'Marked': Drupal.t('Marked'),
      'No dismissals found.': Drupal.t('No dismissals found.'),
      'Page path': Drupal.t('Page path'),
      'Page': Drupal.t('Page'),
      'Pages': Drupal.t('Pages'),
      'Pages with alerts': Drupal.t('Pages with alerts'),
      'Panel': Drupal.t('Panel'),
      'Path': Drupal.t('Path'),
      'Published': Drupal.t('Published'),
      'Publisher type': Drupal.t('Published'),
      'Recent': Drupal.t('Recent'),
      'Recent accessibility alerts': Drupal.t('Recent accessibility alerts'),
      'Recent alerts': Drupal.t('Recent alerts'),
      'Sort by': Drupal.t('Sort by'),
      'Title': Drupal.t('Title'),
      'Type': Drupal.t('Type'),
    };

    // Fold view filters into a collapsed details element.
    if (context === document && CSS.supports('selector(:is(body))')) {
      const filters = document.querySelector('.view-filters > form');
      const exportView = document.querySelector('.editoria11y-export');
      if (filters) {
        const details = document.createElement('details');
        details.classList.add('claro-details', 'ed11y-filters');
        const summary = document.createElement('summary');
        summary.classList.add('claro-details__summary');
        summary.textContent = filters.querySelector('.form-item--sort-by') ?
          Drupal.t('Filter and sort') :
          Drupal.t('Filter');
        details.appendChild(summary);
        /*
        if (document.querySelector('#edit-reset')) {
          details.open = true;
        }
        */
        filters.insertAdjacentElement('beforebegin', details);
      }

      // Add ref parameters to links for tracebacks.
      const ed1s = document.querySelectorAll('a[href][data-ed1ref]');
      ed1s?.forEach((el) => {
        let append = el.getAttribute('href');
        append += append.indexOf('?') > -1 ? '&ed1ref=' : '?ed1ref=';
        append += encodeURIComponent(el.getAttribute('data-ed1ref'));
        el.setAttribute('href', append);
      });

      // Todo move to CSA: Only show content issues to non-dev roles.
      if (!drupalSettings.editoria11y.profile || drupalSettings.editoria11y.profile === 'content') {
        const edDev = document.querySelectorAll('.views-field-dev-results, .views-field-dev-count');
        edDev.forEach((el) => {
          el.setAttribute('role', 'presentation');
          el.style.setProperty('display', 'none');
        });
      }

      // Todo move to export: Remove CSA elements from tables.
      const exportContent = document.querySelector('.editoria11y-export-overview .view-header');
      if (exportContent) {
        exportContent.classList.add('views-exposed-form');
        const exportBar = document.createElement('div');
        exportContent.appendChild(exportBar);
        exportBar.classList.add('editoria11y-export-actions');
        const exportIcons = document.querySelector('.editoria11y-export-overview .feed-icons');
        if (exportIcons) {
          exportBar.append(exportIcons);
        }
        const exportBack = document.querySelector('.editoria11y-export-overview .more-link a');
        if (exportBack) {
          exportBar.append(exportBack);
          exportBack.classList.add('button');
        }
        const exportLinks = document.querySelectorAll('.editoria11y-export-overview .views-display-link');
        if (exportLinks) {
          exportLinks.forEach((link) => {
            link.classList.add('button');
            exportBar.append(link);
          });
        }
      }

      // Set up path reset buttons.
      let resetPath = document.querySelector('.ed11y-reset-this-path');
      let resetDismissal = [];
      let resetDismissalWrapper = document.querySelectorAll('span.ed11y-reset-dismissal:not(.done)');
      resetDismissalWrapper.forEach(el => {
        const button = document.createElement('button');
        button.classList.add('ed11y-reset-this-dismissal', 'action-link', 'action-link--icon-trash');
        const eid = el.querySelector('.ed11y-page-id');
        const id = el.querySelector('.ed11y-dismissal-id');
        eid.hidden = true;
        id.hidden = true;
        button.appendChild(eid);
        button.appendChild(id);
        el.appendChild(button);
        resetDismissal.push(button);
        el.classList.add('done');
      });

      if (drupalSettings.editoria11y.api_url && ((drupalSettings.editoria11y.admin && !!resetPath) || resetDismissal.length > 0)) {
        // Set up error handling.
        const messages = new Drupal.Message();
        const handleErrors = function (data) {
          // Server-provided strings; escape before Drupal.Message renders
          // them as markup.
          messages.add(`${Drupal.checkPlain(String(data.message))}: ${Drupal.checkPlain(String(data.description))}`, { type: 'warning' });
        };

        let apiUrl = drupalSettings.editoria11y.api_url;
        let sessionUrl = drupalSettings.editoria11y.session_url;

        // Get cross-request token for API
        let csrfToken = false;
        let csrfRequest = null;
        const getCsrfToken = function () {
          // Single in-flight request shared by concurrent posts.
          if (!csrfRequest) {
            csrfRequest = fetch(`${sessionUrl}`, {
              method: 'GET',
            })
              .then((res) => {
                if (!res.ok) {
                  throw new Error(`Editoria11y could not fetch a session token (HTTP ${res.status}).`);
                }
                return res.text();
              })
              .then((token) => {
                csrfToken = token;
                return token;
              })
              .catch((error) => {
                csrfRequest = null;
                throw error;
              });
          }
          return csrfRequest;
        };

        let hidables = [];

        // Send a request to the purge API endpoint.
        let postData = async function (data, action) {
          try {
            if (!csrfToken) {
              await getCsrfToken();
            }
            // Prefer the discrete URLs the module attaches; fall back to
            // deriving from the report URL for cached settings from older
            // module versions.
            const url = action === 'page' ?
              (drupalSettings.editoria11y.purge_page_url ?? apiUrl.replace('results/report', 'purge/page')) :
              (drupalSettings.editoria11y.purge_dismissal_url ?? apiUrl.replace('results/report', 'purge/dismissal'));
            const res = await fetch(url, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
              },
              body: JSON.stringify(data),
            });
            const body = await res.json();
            if (!res.ok || body.message === 'error') {
              handleErrors(body);
            }
            else {
              messages.add(Drupal.t('Deleted.'), { type: 'status' });
            }
          }
          catch (error) {
            console.log(error);
            messages.add(Drupal.t('Delete failed.'), { type: 'warning' });
          }
        };

        // Responsive tables: drupal.org/project/editoria11y/issues/3536720.
        const deSaw = (el) => {
          const inner = el.querySelector('.tablesaw-cell-content');
          return inner ? inner.textContent.trim() : el.textContent.trim();
          // todo 3.0 rewrite this maybe go back to inner hidden span
        };

        if (!!resetPath) {
          let purgeThisPage = function (event) {
            event.preventDefault();
            let data = {
              page_path: deSaw(event.target.querySelector('.ed11y-api-path')),
            };
            hidables = document.querySelectorAll('.view-editoria11y-results td');
            hidables?.forEach(el => {
              el.parentElement.removeChild(el);
            });
            postData(data, 'page').then();
          };
          resetPath.removeAttribute('hidden');
          resetPath.querySelector('a')?.addEventListener('click', purgeThisPage);
        }
        else if (!!resetDismissal) {
          let purgeThisDismissal = function (event) {
            event.preventDefault();
            const eid = deSaw(event.target.querySelector('.ed11y-page-id'));
            const dismissal = deSaw(event.target.querySelector('.ed11y-dismissal-id'));
            const data = {
              dismissal_id: dismissal,
              pid: eid,
            };
            const tr = event.target.closest('tr');
            let previous = tr?.previousElementSibling;
            postData(data, 'dismissal').then();
            previous?.querySelector('a')?.focus();
            window.setTimeout((tr) => {
              tr.parentElement.removeChild(tr);
            }, 500, tr);
          };
          resetDismissal.forEach(el => { el.addEventListener('click', purgeThisDismissal); });
        }
      }

      // Alert for missing views.
      const views = ['pages', 'results', 'dismissals'];
      views.forEach((type) => {
        const missingView = document.querySelector(`.ed11y-${type}-view h2:last-child`);
        if (missingView) {
          const viewsMessage = Drupal.t('<p>The Editoria11y <strong><em>@type</em></strong> view is missing.</p>', { '@type': type });
          const viewsExplanation = Drupal.t('<p>There are three view configurations this module installs, found in its /config/install folder. Each must be installed for this dashboard to display results.</p>');
          const viewsInstruction = Drupal.t('<p>To fix: copy and paste the contents of <strong>/editoria11y/config/install/views.view.ed11y_@type.yml</strong> into a "View" type import at <a href="/admin/config/development/configuration/single/import">/admin/config/development/configuration/single/import</a>. If you sync your configuration to the filesystem, be sure to include these Views in your repository.</p>', { '@type': type });
          const message = new Drupal.Message();
          message.add(viewsMessage + viewsExplanation + viewsInstruction, { type: 'error' });
        }
      });

      if (!window.drupalTranslations || drupalSettings.path.language === 'en') {
        document.body.classList.add('ed11y-no-th-hyphens');
      }

      // Drupal.t does not detect variables so we have to preload.
      const eT = function (el) {
        if (!el.children.length) {
          if (el.textContent && translatables[el.textContent.trim()]) {
            el.textContent = translatables[el.textContent.trim()];
          }
        } else {
          const inners = el.querySelectorAll('*');
          inners?.forEach((inner) => {
            eT(inner);
          });
        }
      };
      const eTS = document.querySelectorAll('h1, h2, .tabs a, option, label, summary, .view-empty, .form-item__description, th, button, main a:not(table a)');
      eTS?.forEach(el => {
        eT(el);
      });
      document.querySelectorAll('[type="submit"]').forEach(el => {
        el.value = translatables[el.value] ?? el.value;
      });
    }
  },
};
