export function reportSyncDone() {
  "use strict";
  // Used by crawler.
  try {
    if (parent && parent.ed11ySynced) {
      parent.postMessage({ done: `${document.location}` }, document.origin);
    }
  } catch (e) {
    console.error(e);
  }
}

export function sync(options, urlParams) {
  "use strict";

  const dS = drupalSettings.editoria11y;

  const endpointUrl = function (action) {
    // Prefer the discrete URLs the module attaches; fall back to deriving
    // from the report URL for settings cached by older module versions.
    const apiRoot = dS.api_url.replace('results/report', '');
    switch (action) {
      case 'dismiss':
        return dS.dismiss_url ?? `${apiRoot}dismiss`;
      case 'purge/page':
        return dS.purge_page_url ?? `${apiRoot}purge/page`;
      default:
        return dS.api_url;
    }
  };

  let csrfToken = false;
  let csrfRequest = null;
  const getCsrfToken = function () {
    // Single in-flight request: concurrent posts share one token fetch
    // instead of each starting their own.
    if (!csrfRequest) {
      csrfRequest = fetch(`${dS.session_url}`, {
        method: "GET"
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
        .catch((err) => {
          csrfRequest = null;
          throw err;
        });
    }
    return csrfRequest;
  };

  let postData = async function (action, data) {
    try {
      if (!csrfToken) {
        await getCsrfToken();
      }
      const res = await fetch(endpointUrl(action), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify(data),
      });
      if (!res.ok) {
        console.error(`Editoria11y sync failed (HTTP ${res.status}).`);
      }
    } catch (error) {
      console.error('Error:', error);
    }
    // Always signal the crawler, so a failed post cannot hang a crawl.
    reportSyncDone();
  };

  // Purge changed aliases & deleted pages.
  const ed1Ref = urlParams.has('ed1ref') ? decodeURIComponent(urlParams.get('ed1ref')) : false;
  if (ed1Ref && dS.pid && String(ed1Ref) !== String(dS.pid)) {
    postData('purge/page', { pid: ed1Ref }).then();
  }

  let rawResults = [];
  let results = {};
  let oks = [];
  let total = 0;
  let devTotal = 0;
  let sentInitial = false;
  let sendAfterNextResults = false;
  const contentSyncRoot = options.contentSyncRoot ?? options.checkRoot;

  const debounce = (callback, wait) => {
    let timeoutId = null;
    return (...args) => {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(() => {
        callback.apply(null, args);
      }, wait);
    };
  };

  let extractResults = function () {
    results = {};
    oks = [];
    total = 0;
    devTotal = 0;
    rawResults.forEach(result => {
      const testKey = result.test;
      const contentResult = result.element.closest(contentSyncRoot);
      const addToContent = contentResult ? 1 : 0;
      let testName = Drupal.ed11yLang.lang.testNames[`${testKey}`];
      if (!testName) {
        console.warn('Editoria11y test is missing a key: ', result);
        testName = testKey;
      }
      if (!(result.dismissalStatus && result.dismissalStatus.includes('ok'))) {
        // log all items not marked as OK
        if (results[testKey]) {
          results[testKey] = {
            content_count: results[testKey].content_count + addToContent,
            dev_count: results[testKey].dev_count + 1 - addToContent,
            result_name: testName,
          };
        } else {
          results[testKey] = {
            content_count: addToContent,
            dev_count: 1 - addToContent,
            result_name: testName,
          };
        }
        total += addToContent;
        devTotal += 1 - addToContent;
      }
      else {
        oks.push({
          resultKey: result.test,
          dismissalKey: result.dismiss,
          result_name: testName,
          action_type: result.dismissalStatus,
        });
      }
    });
  };

  let sendResults = function () {
    window.setTimeout(function () {
      total = 0;
      extractResults();
      let data = {
        page_title: dS.page_title,
        page_path: dS.page_path,
        entity_id: dS.entity_id,
        content_total: total,
        dev_total: devTotal,
        language: dS.content_language ?? dS.lang,
        entity_type: dS.entity_type, // node or false
        route_name: dS.route_name, // e.g., entity.node.canonical or view.frontpage.page_1
        results: results,
        oks: oks,
      };
      postData('results/report', data);
      // Short timeout to let execution queue clear.
    }, 100);
  };

  if (dS.dismissals && dS.sync !== 'dismissals' && dS.sync !== 'disable') {
    const debouncedInitialSend = debounce(function () {
      sentInitial = true;
      if (rawResults.length > 0 || dS.pid) {
        sendResults();
      } else {
        reportSyncDone();
      }
    }, 300);

    document.addEventListener('ed11yResults', function (e) {
      rawResults = e.detail.results;
      if (!sentInitial) {
        debouncedInitialSend();
      } else if (sendAfterNextResults) {
        sendAfterNextResults = false;
        sendResults();
      }
    });
  }

  let dismissalsCache = {};
  let dismissalsData = {
    dismissals: [],
  };

  const sendDismissals = debounce(() => {
    // Get dynamic title from edit pages.
    // todo: Canvas title selector?
    extractResults();
    const editableTitleField = document.querySelector('#edit-title-wrapper input, #edit-name-wrapper input, #edit-name input');
    const data = {
      results: results,
      oks: oks,
      dismissals: dismissalsData.dismissals,
      page_title: drupalSettings.path.currentPathIsAdmin &&
        editableTitleField && editableTitleField.value ?
        editableTitleField.value :
        dS.page_title,
      page_path: dS.page_path,
      content_total: total,
      dev_total: devTotal,
      entity_id: dS.entity_id,
      language: dS.content_language ?? dS.lang,
      entity_type: dS.entity_type,
      route_name: dS.route_name,
    };
    dismissalsData.dismissals = [];
    dismissalsCache = {};

    postData('dismiss', data).then();
  }, 250);


  // @Todo: Can move to CSA JS. And does it need split config?
  let passingGlobalDismissal = false;
  if (dS.allow_ok === 'okAll') {
    // Allow certain tests to dismiss across site.
    document.addEventListener('ed11yPop', (e) => {
      const okTests = [
        'PDF', 'EMBED', 'CONTRAST_WARNING', 'META_LANG', 'BTN', 'DUPLICATE', 'LABELS', 'IDENTICAL', 'DOCUMENT',
      ];
      const okFind = new RegExp(okTests.join('|'));
      if (!e.detail.tip.shadowRoot.querySelector('.ok-site') && (
        e.detail.result.type === 'warning' && (
          e.detail.result.outsideContentRoots ||
          e.detail.result.test.replace(okFind, '') !== e.detail.result.test
        )
      )
      ) {
        const ok = e.detail.tip.shadowRoot.querySelector('.dismiss.ok:not(.appended)');
        if (ok) {
          ok.classList.add('appended');
          ok.dataset.pushToAll = 'false';

          const okAll = ok.cloneNode(true);
          okAll.textContent = Drupal.Ed11y.Lang.langStrings.dismissOnSite;
          okAll.classList.remove('ok');
          okAll.classList.add('ok-site');

          const details = e.detail.tip.shadowRoot.querySelector('.ed11y-bulk-actions');
          const detailsContent = details.querySelector('.ed11y-bulk-actions-content');
          detailsContent.insertAdjacentElement('beforeend', okAll);
          details.classList.remove('ed11y-hidden');

          okAll.addEventListener('click', (e) => {
            e.preventDefault();
            passingGlobalDismissal = true;
            ok.click();
          });
        }
      }
    });
  }

  const prepareDismissal = function (detail) {
    if (!!detail) {
      if (detail.dismissAction === 'reset') {
        dismissalsCache = {};
        dismissalsData.dismissals.push(
          {
            action_type: 'reset', // reset or resetAll (siteWide)
            result_key: detail.dismissTest, // which test is sending a result
            element_id: detail.dismissKey, // some recognizable attribute of the item marked
          },
        );
        if (dS.sync !== 'dismissals') {
          sendAfterNextResults = true;
          window.setTimeout(function () {
            if (sendAfterNextResults) {
              sendAfterNextResults = false;
              sendResults();
            }
          }, 500);
        }
      } else if (detail.dismissTest in dismissalsCache && dismissalsCache[detail.dismissTest].includes(detail.dismissKey)) {
        return false;
      } else {
        // Send if we have not already sent the same key.
        if (!(detail.dismissTest in dismissalsCache)) {
          dismissalsCache[detail.dismissTest] = [detail.dismissKey];
        } else {
          dismissalsCache[detail.dismissTest].push(detail.dismissKey);
        }
        const action_type = passingGlobalDismissal ? 'okAll' : detail.dismissAction;
        passingGlobalDismissal = false;

        let testName = Drupal.ed11yLang.lang.testNames[`${detail.dismissTest}`];
        if (!testName) {
          testName = detail.dismissTest;
        }

        dismissalsData.dismissals.push(
          {
            result_name: testName, // which test is sending a result
            result_key: detail.dismissTest, // which test is sending a result
            element_id: detail.dismissKey, // some recognizable attribute of the item marked
            action_type: action_type, // ok, ignore or reset
          }
        );
        if (action_type.includes('ok') && dS.sync !== 'dismissals') {
          sendAfterNextResults = true;
          window.setTimeout(function () {
            if (sendAfterNextResults) {
              sendAfterNextResults = false;
              sendResults();
            }
          }, 500);
        }
      }
      sendDismissals();
    }
  };
  if (dS.dismissals && dS.sync !== 'disable') {
    document.addEventListener('ed11yDismissalUpdate', function (e) {
      prepareDismissal(e.detail);
    }, false);
  }
}
