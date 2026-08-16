# Editoria11y

## Contents

* Introduction

## Requirements

This module requires no modules outside of Drupal core.

## Introduction

Editoria11y (editorial accessibility ally) is a user-friendly accessibility
checker that addresses three critical needs for content authors:

1. It runs automatically. Modern spellcheck works so well because it is always
   running; put spellcheck behind a button and few users remember to run it!
2. It focuses exclusively on straightforward issues a content author can easily
   understand and easily fix. Comprehensive testing should be a key part of site
   creation, but if a tool is going to run automatically, it will do more harm
   than good if it is constantly alerting on code they do not understand
   and cannot fix.
3. It runs in context. Views, Layout Builder, Paragraphs and all the other
   modules Drupal uses to assemble a page means that tools that run inside
   CKEditor cannot "see" many of the issues on a typical page.

[Demo](https://itmaybejj.github.io/editoria11y/demo/)
| [Project Page](https://www.drupal.org/project/editoria11y)
| [Issue Queue](https://www.drupal.org/project/issues/editoria11y?categories=All)

### The authoring experience

* On each page load Editoria11y places a small toggle button at the bottom right
  of the screen with an issue count. Users can press the button to view details
  of any alerts or access additional tools ("full check"), including visualizers
  for the document outline and image alt attributes.
* In edit mode, it also checks within CKEditor5 and Gutenberg edit boxes. CKEditor4 boxes cannot be checked. 
* Depending on configuration settings, the panel may open automatically if new
  issues are detected.

## Installation

* [Follow standard installation for a contrib module](https://www.drupal.org/documentation/install/modules-themes/modules-8)
  .
* If you are installing from the command line, do note the "eleventy" when
  spelling the module's name!

### Adding CSA Features

The [Community Supported Add-ons project](https://editoria11y.com) funds Editoria11y development through a contribute-what-you-can model.

The editoria11y_csa submodule adds many features for project members, such as developer tests, tools and reports; a custom test builder; dashboard report maintenance tools and a site crawler.

Note that all **independent** activations use one site from your license. Development sites should sync down a copy of the activated production database. Teams with multiple developers should run `drush ed11y-lock` after activation to prevent modifications to shared activations from affecting the production copy.

Activation:
Note that all **independent** activations use one site from your license. Development sites should sync down a copy of the activated production database. Teams with multiple developers should run `drush ed11y-lock` after activation to prevent modifications to shared activations from affecting the production copy.

Activation:
* Install the submodule
* [Become a project member](https://editoria11y.com/license)
* Add your license key on the CSA features tab of the Editoria11y settings page, then activate it.

Keys can also be managed via Drush:
* Assign a key via `drush ed11y-set-key MY-LICENSE-KEY`
* Activate a key via `drush ed11y-activate`
* Prevent modifying the license in the GUI via `drush ed11y-lock`
* Production sites check for renewals regularly, but if a license expires before renewal and the site enters an expired state, run `drush ed11y-check-renewal` to force a refresh after renewing in the [Freemius customer portal](https://customers.freemius.com/login)
* Add your license key on the CSA features tab of the Editoria11y settings page, then activate it.

Keys can also be managed via Drush:
* Assign a key via `drush ed11y-set-key MY-LICENSE-KEY`
* Activate a key via `drush ed11y-activate`
* Prevent modifying the license in the GUI via `drush ed11y-lock`
* Production sites check for renewals regularly, but if a license expires before renewal and the site enters an expired state, run `drush ed11y-check-renewal` to force a refresh after renewing in the [Freemius customer portal](https://customers.freemius.com/login)

Keys can also be managed by themes and modules in update hooks:

```php
/**
 * Assign and activate the Editoria11y CSA license.
 */
function MYMODULE_update_10001(): void {
  /** @var \Drupal\editoria11y_csa\LicenseManager $license_manager */
  $license_manager = \Drupal::service('editoria11y_csa.license_manager');

  // Only activate in production.
  // Sync the activated database to local/dev/qa.
  if ($license_manager->isDevEnvironment()) {
    return;
  }

  try {
    // Call the Freemius API and activate a license for this site.
    $license_manager->activateLicense('MY-LICENSE-KEY');

    // Or to store an inactive key to use in the GUI:
    // $license_manager->saveLicenseKey('MY-LICENSE-KEY')

  }
  catch (\Drupal\editoria11y_csa\Exception\LicenseManagerException $e) {
    throw new \Drupal\Core\Utility\UpdateException('Editoria11y license activation failed: ' . $e->getMessage());
  }
}
``` 

### Keeping keys out of the codebase

To avoid committing a license key to Git or having it travel in database dumps synced to developer workstations, install the [Key module](https://www.drupal.org/project/key) and create a key with the machine name `editoria11y_license`. When no license key is present in the DB, Editoria11y CSA automatically checks for a key with this name.

The Key module key can be locally overridden from the module settings page or `drush ed11y-set-key`.

Key's providers let you source the secret from wherever fits your workflow:

* **File outside the web root** — a File provider pointing at e.g. `/etc/drupal-secrets/editoria11y_license` keeps the value off the repo and out of the database.
* **Environment variable** — an Environment provider reading something like `ED11Y_LICENSE_KEY` works with Platform.sh/Upsun variables, Pantheon secrets, Acquia Cloud environment variables, DDEV's `web_environment`, and CI secret stores (GitHub Actions, GitLab CI).
* **Encrypted-in-repo file** — commit a file encrypted with git-crypt, SOPS, or age, decrypt it during deploy, and point a File provider at the decrypted path. The repo stays safe to share publicly; only production has the plaintext.

See the [Key module guide](https://www.drupal.org/docs/contributed-modules/key) for details.

## Configuration

* Configure user permissions: on install, Editoria11y assigns the "View
  Editoria11y Checker" to user roles with common content-editing permissions.
  This is an inexact science; check Administration » People » Permissions to
  make sure all editorial roles have been assigned this position, and custom
  non-editorial roles (e.g., custom advanced webform users)
  have not. If a logged in site editor does not see Editoria11y, they are likely
  missing this permission.
* Configure elements to scan and ignore (Configuration » Content Authoring »
  Editoria11y):
    * By default, Editorially scans the HTML "body" element. Check to see if
      that makes sense for your site, and override if necessary on the module's
      configuration page. For many sites, something like "main" or "#main" is a
      better setting.
    * Some content just does not play nice with this type of tool; embedded
      social media feeds, for example. Add selectors for page elements you want
      the scanner to skip over. Optionally also flag these elements' containers
      as needing manual review.
* Check that popups are visible.
    * If the theme CSS sets `overflow: hidden` on containers, popups may be
      truncated. Selectors for these containers can be set on the Editoria11y
      configuration page, and this CSS will be temporarily overridden when a
      popup is open.
    * If the theme has components that show and hide content (carousels, tabs,
      accordions), you may wish to use the JS events listed below to reveal
      hidden content with errors when the user tries to jump to the hidden
      error.
* Check that Editoria11y is not running on content that is currently being
  edited.
    * By default, Editorially does not run on administrative paths, or if it
      detects that in-place editing is happening (e.g., Layout Builder). If you
      have inline editing enabled, check to see if Editoria11y is
      detecting/ignoring content that is actively being edited. If not, add a
      selector for elements present during editing to the module's configuration
      page under "Disable the scanner if these elements are detected." And do
      tell us about the conflict! If it is a common module we will add it to the
      default ignore list.
* If you hit a white screen of death when setting up Views filters, check that you don't temporarily need to patch [core's bundle bug](https://www.drupal.org/project/drupal/issues/2988983#comment-14971083) to finish creating the View.

## Extending and modifying Editorially

### Adding custom Tests

First, in the module config, add 1 to the "Custom tests" options so it knows to
watch for the tests.

Then [create a Drupal JS library](https://www.drupal.org/docs/develop/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml) with your tests in your theme or module, using the [guide to writing custom tests](https://editoria11y.princeton.edu/configuration/#customtests).

Then call your JS library for users with sufficient permissions to see 
Editoria11y:

```php
/**
 * Attaches a custom library to all pages when user has permission.
 *
 * {@inheritdoc}
 */
function MYMODULE_page_attachments(array &$page) {
  if (!\Drupal::currentUser()->hasPermission('view editoria11y checker')) {
    return;
  }

  $page['#attached']['library'][] = 'MY-MODULE/MY-LIBRARY-NAME';
} 

```

### Modifying options at runtime in PHP

To make changes in Drupal, use modify the computed page attachments using hook_editoria11y_alter_config.

### Modifying options at runtime in JS

Before initiating the Editoria11y library, the module checks at the JavaScript
level to see if a module or theme has requested to modify the options generated
from the module config.

This is done by setting editoria11yOptionsOverride to true, and then providing
an editoria11yOptions function that will process the options object.

Example use (in JS in your theme or module) to provide a default ignored item,
or add it to the list provided in the GUI:

```js
var editoria11yOptionsOverride = true;
var editoria11yOptions = function(options) {
  if (options['ignoreElements'] === false) {
    // Replace with default value.
    options['ignoreElements'] = `.example`;
  } else {
    // Append default value with comma separator.
    options['ignoreElements'] = `${options['ignoreElements']}, .example`
  }
  return options;
}
```
You can set or override any of the [library's parameters](https://editoria11y.princeton.edu/configuration/#parameters) at this time.

### Attaching custom CSS

First set up the options override, as above, then override theme parameters as
needed.

For simple overrides of color and font, try using the exposed parameters:
```js
var editoria11yOptionsOverride = true;
var editoria11yOptions = function(options) {
  sleekTheme: {
    bg: '#fffffe',
      bgHighlight: '#7b1919',
      text: '#20160c',
      primary: '#276499',
      primaryText: '#fffdf7',
      button: 'transparent', // deprecate?
      panelBar: '#fffffe',
      panelBarText: '#20160c',
      panelBarShadow: 'inset 0 -1px #0002, -1px 0 #0002',
      panelBorder: '0px',
      activeTab: '#276499',
      activeTabText: '#fffffe',
      outlineWidth: 0,
      borderRadius: '1px',
      ok: '#1f5381',
      warning: '#fad859',
      alert: '#b80519',
      focusRing: '#007aff',
  },
  buttonZIndex: 9999, 
  baseFontSize: '14px', // px
  baseFontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,
                  "Helvetica Neue",Arial,sans-serif',

  return options;
}
```

If you want to provide your own CSS entirely, place a file in the filesystem 
and add it to the 'cssUrls' array, e.g.:
```js
var editoria11yOptionsOverride = true;
var editoria11yOptions = function(options) {
  options['cssUrls'].push('/path/to/my/file/overrides.css')
  return options;
}
```

### Using JS events provided by the library

Reference the [library events documentation](https://editoria11y.princeton.edu/configuration/#js-events) for current tips.

#### The main panel has opened

Useful for preparing content for a user who is scrolling around looking for
errors -- e.g., opening accordions if their content has an error, or disabling a
sticky menu that covers part of the content.

```js
document.addEventListener("ed11yPanelOpened", function (event) {
  // jQuery('.example').addClass('editoria11y-active');
});
```

#### Postprocess a tooltip

Use this to modify the content of the tooltip. It provides access to the tip itself, the test name, and the marked element.

If you want to react by switching to a tab/slide/accordion containing the error,
use the next event instead.

```js
document.addEventListener('ed11yPop', (e) => {
  const tipContent = e.detail.tip.shadowRoot.querySelector('.content');
  const test = wrapper.getAttribute('data-test');
  const markedElement = e.detail.result.element;
  // Modify as needed.
})
 ```

#### Preprocess a tooltip in a container you asked to be alerted about

Thrown for elements listed in the "Theme JS will handle revealing hidden
tooltips" configuration option, when the panel's "jump to the next issue" link
tries to open a matching tooltip.

*After* throwing this event, Editoria11y pauses for half a second, giving your
theme a moment to open an accordion or tab panel (etc.) before Editoria11y tries
to transfer focus to the hidden tip:

```js
document.addEventListener('ed11yShowHidden', function(e) {
  if (!e.detail.viaJump) {
    // Filter out tips opened via hover.
    return;
  }
  const toggle = Drupal.Ed11y.State.results[e.detail.result].toggle;
  const result = Drupal.Ed11y.State.results[e.detail.result].element;
  if (result.closest('.my-example-widget')) {
    // Open the accordion or tab.
  }
}
```

## Troubleshooting

### The checker reports false positives on an element

* Add a selector for the element to the "containers to ignore" configuration
  setting. If the false positive is really a mistake, please do report an issue
  to the module maintainers.

### Editors find the panel opening automatically to be annoying.

* On the config page, change the setting for when the panel should auto-open
  from "Smart" or "Always" to "Never." Editoria11y will still *check* every page
  and insert an issue count on the toggle, but it will stop auto-highlighting
  errors.

### The checker does not appear

* If it does not appear for *at all*
    * Clear cache...
    * Make sure a selector has not been added to the "Disable the scanner if..."
      configuration setting that matches something on every page.
    * Make sure it's not hidden due to config or CSS. Use your browser 
      inspector to inspect the page and see if an element with an ID of 
      "ed11y-main-toggle" is present. If it is _there_, see if something in 
      your theme is covering it (z-index) or clipping it (overflow: hidden).
      You may need to use a little CSS to increase the z-index of 
      `#ed11y-panel` or translate it up or left to get it out from under
      another component.
    * Make sure there are no JS errors in your browser console. Typos in
      selectors on the config page _will_ throw an error and block the checker
      from running.
    * Make sure something (anything) is set in the "Tests" preference for when
      the panel should open automatically. If this configuration option gets
      unset sometimes the panel never appears.
    * If you are running Advagg, don't check the button to "force preprocess for all files." See issue "[Does not work with Advanced CSS/JS Aggregator + jSqueeze](https://www.drupal.org/project/editoria11y/issues/3230850)"
* If it does not appear for some *users*:
    * Make sure the user's role has permission granted to "View the Editoria11y
      Checker."
* If it does not appear on some *pages*
    * Make sure a selector has not been added to the "Disable the scanner if..."
      configuration setting that matches something on the page.
    * Check for JS errors in your browser console.

### You don't like the default error messages

Tip test names and contents can be overridden at runtime by overriding the test ID in `Drupal.Ed11y.Lang.testNames` and `Drupal.Ed11y.Lang.langStrings`, respectively. 

The `Lang` object is populated by the active language. This will be the content language, not the interface language. If that matters for your override, check `Drupal.Ed11y.Lang.langStrings.LANG_CODE`.

### The checker slowed down after configuration

* Editoria11y should finish scanning and painting tooltips in less than 100ms, even on very long pages. If you find it is taking longer, try constraining the checked area a but more, or turning off watching for changes.

## Maintainers

Editoria11y is provided to the community thanks to
the [Digital Accessibility](https://accessibility.princeton.edu/) initiatives at
Princeton
University's [Office of Web Development Services](https://wds.princeton.edu/).
- [John Jameson](https://www.drupal.org/u/itmaybejj), Digital Accessibility Developer
- [Brian Osborne](https://www.drupal.org/u/bkosborne), Web Solutions Architect


### Acknowledgements

The Editoria11y library began as a fork of
the [Sa11y](https://sa11y.netlify.app/) accessibility checker, which
was created by Digital Media Projects, Computing and Communication Services (
CCS) at Toronto Metropoliton University:

- [Adam Chaboryk](https://github.com/adamchaboryk), IT accessibility specialist
- Benjamin Luong, web accessibility assistant
- Arshad Mohammed, web accessibility assistant
- Kyle Padernilla, web accessibility assistant

Sa11y itself was an adaptation
of [Tota11y by Khan Academy](https://github.com/Khan/tota11y), was built
with [FontAwesome icons](https://github.com/FortAwesome/Font-Awesome) and is
powered with jQuery.
