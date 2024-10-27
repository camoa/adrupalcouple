# CONTENTS OF THIS FILE

 * Introduction
 * Installation
 * Requirements
 * Recommended modules
 * Configuration / Customization
 * Automatic attribution of resources
 * Cookies
 * Troubleshooting
 * Maintainers

## INTRODUCTION

This module implements the [Klaro! consent manager JS-Library](https://klaro.kiprotect.com) for Drupal and adds an interface to configurate and customize klaro, manage apps and purposes, manage texts and their translations as well as automatically setting the required html-attributes to external resources for the JS-library to work. It also adds the ability to block unknown(No app configurated) external resources by default.

### What is it good for?
The primary use case for this module is to:

- **Provide** a configurable consent/cookie manager for the site visitor, to give (or decline) consent to predefined "apps".
- **Satisfy** the [EU cookie guidelines](https://edpb.europa.eu/sites/edpb/files/files/file1/edpb_guidelines_202005_consent_en.pdf) (PDF)
- **Manipulate** scripts and embeds (iframe, img, audio, video) added by editors or Drupal and/or its modules to adhere to the users' preference.

### Goals
- Privacy by default.
- Fully customizable, managable and translatable consents (*apps*).
- Fully customizable, managable and translatable *purposes*.
- Fully customizable and translatable texts of the consent manager.
- Automatically set all required html attributes and/or contextual blocking elements to external resources.

## INSTALLATION

The installation of this module is like other Drupal modules.

1. Place the klaro Drupal module in the `modules` directory of your Drupal installation.
2. Place the [`klarohq/klaro-js`](https://github.com/klaro-org/klaro-js/tags) javascript library in your site's `libraries` folder.
3. Enable the 'Klaro!' module in 'Extend': `/admin/modules`.
4. Set up user permissions: `/admin/people/permissions#module-klaro`, this module will add 2 permissions: `Administer Klaro!` to administrate klaro from in the backend. And `Use Klaro! UI` for clients/users to manage their consents, so make sure to add this one at least for guests/anonymous users.

We recommend to use composer for step 1 and 2, for installing the module use:

`composer require drupal/klaro`

For installing the klaro js library it is recommended to use the composer-merge-plugin and include the composer.libraries.json of this module into the extra merge-plugin secion of your root composer.json:
```
"extra": {
  "merge-plugin": {
      "include": [
          "web/modules/contrib/klaro/composer.libraries.json"
      ]
  }
}
```
[More infos how to do that](https://www.drupal.org/docs/8/modules/webform/webform-frequently-asked-questions/how-to-use-composer-to-install-libraries-for-the-webform-module)

However, just make sure the two files `klaro.min.css` and `klaro-no-css.js` are placed at `{web_dir}/libraries/klaro/dist` and it will work.

## REQUIREMENTS

This module only requires the [`klarohq/klaro-js`](https://github.com/klaro-org/klaro-js)
javascript library. No other Drupal modules are required.

## RECOMMENDED MODULES

* [Configuration Translation](https://www.drupal.org/docs/8/core/modules/config-translation) for multilingual sites.

## CONFIGURATION / CUSTOMIZATION

The module comes with some pre-defined *purposes*, such as "CMS" for Drupal-related cookies or "External embeds". These are used to group individual cookies, trackers etc. It also ships with some *app* settings, e.g. for analytics provided by Matomo, based on your site's installed modules.

For Matomo the module provides two different configurations. If you want to track every visit and block only cookies, please use the app `matomo_cookies`. You have to add an additional javascript line to the Matomo config on `/admin/config/system/matomo`. Insert in "Advanced settings" in "Code snippet (before)" the following command: `_paq.push(['requireCookieConsent']);`. For further information see https://matomo.org/faq/how-to/using-klaro-consent-manager-with-matomo/#klaro-open-source

### Backend / UI

* Manage general settings&nbsp;`/admin/config/user-interface/klaro`.
* Manage apps&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`/admin/config/user-interface/klaro/apps`.
* Manage purposes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`/admin/config/user-interface/klaro/purposes`.
* Manage texts&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`/admin/config/user-interface/klaro/texts`.

### Styling
At `/admin/config/user-interface/klaro` settings->styling you can add additional css classes that will be added to the consent-notice, the consent-modal and the contextual-blocking element, so you can apply your own css to it.
in the field `Override Klaro css variables` you can make changes to positioning and the used theme, for example you can enter `light, right, bottom` to use the light-theme and position the cookie notice in the bottom right corner. more infos you find [here](https://github.com/kiprotect/klaro/blob/fb4e393d2cd8aeedc3e751d103dfbfd35ffae0f2/src/themes.js)

The setting "additional css classes" will be added to all klaro elements (cookie-notice, consent-dialog, contextual blocking element), so you can apply your own css to it.
For the toggle button shipped with this module, there is an override class added `klaro_toggle_dialog_override` so you can overwrite the styles with this css selector: `klaro_toggle_dialog.klaro_toggle_dialog_override`

### Open the Klaro! consent manager modal

* If the user saves a consent, the consent modal and/or cookie notice will disappear in the vanilla JS-Library until the cookies that save this information are deleted.
* This module ships a button to toggle the dialog, you can enable/disable it `/admin/config/user-interface/klaro` Settings->General->Show button to toggle the consent modal.
* If you like to provide own links or buttons to open the consent dialog at any time, you can just add a `rel`-attribute of any clickable element: `rel="open-consent-manager"`.
* If there is / are already any `rel`-value(s) just extend the value e.g. `rel="nofollow open-consent-manager noindex"`.

## AUTOMATIC ATTRIBUTION OF RESOURCES

The vanilla klaro library requires you to manually add html-attributes `data-name="{app_name}` `data-src="{src}"` and `type="text/plain (for scripts or links)` to your script, iframe, img, audio, video etc. tags.

This module tries to automate the process as far as possible by utilizing js_alter, attachments_alter and a kernelResponseListener that will go through the final html and attribute all external resources. It will also take care for external resources that are added via drupals ajax-insert-commands (AfterCommand, AppendCommand, BeforeCommand, HtmlCommand,PrependCommand, InsertCommand and ReplaceCommand) as well as on the fly added external libraries with drupals add_js ajax command.

It does it by matching against the advanced configuration for each app. You find those under `/admin/config/user-interface/klaro/apps/{app_name}`
In the field `sources` you can add paths as they appear in the src attribute of script, iframe, img, video and audio tags, Enter one source per line, partial matches are supported.
In the field `Embed wrapper classes` you can add css-classnames for which a contextual blocking element will be wrapped. In example a twitter embed not only contains a script tag (which will be blocked by adding the `sources` field) but also some html, in this case a blockquote with the class "twitter-tweet".

### Automatic blocking of unknown resources

Blocking unknown external resources is enabled by default, it only works if "Process final HTML" is activated. It will automatically add an "uknown app" and let users decide to consent to load them, by default a log notice will be added to recent log messages if an external resource was processed, you can disable the loggin and also configurate the label and description of the unknown app or disable this feature completely.

### What will be blocked automatically:
* script tags with src attribute
* img tags with src attribute (Contextual blocking wrap will be added)
* input type"image" tags with src attribute (Contextual blocking wrap will be added)
* link tags with href attribute (e.g fonts)
* audio tags with src attributes or source childnodes with src attribute (Contextual blocking wrap will be added)
* video tags with src attributes or source childnodes with src attribute (Contextual blocking wrap will be added)
* Elements that match the "Embed wrapper class" configuration of an app. (Contextual blocking wrap will be added)
* All above dynamically loaded with AJAX, including attached libraries.

### What will **not** be blocked automatically:
* Inline script tags with a script-body that will itself load external resources and are not added with page_attachments and a configurated attachments-identifier in a klaro-app (You need to set the attributes manually to block it with klaro).
* Includes within css files.

## COOKIES

Klaro saves the user-decisions as cookies(You can also configurate to use the browsers localstorage). However to let klaro also delete the app cookies, i.E when you revoke a consent, there are two places to provide information about them.

1. `/admin/config/user-interface/klaro/apps/{klaro_app}`
   Inside the `Advanced` section you can provide specific cookie information about `name` (regex), `path` and `domain`
2. `/admin/config/user-interface/klaro`
   Inside the `Advanced` section you will find the `Matching cookie domains` textarea. Sometimes scripts may set cookies with dynamic cookie domains. You can add different domains here so that klaro will try to delete all cookies definied in your apps additionally with this cookie domains. So you don't need to add 30 cookie information inside your app just because a script adds several cookies just with 10 differen cookie domains.

## TROUBLESHOOTING

- If the script- / resource **element attibutes** won't appear, then there may be some other module or the theme that is
  preprocessing the tags and stripping out these attributes.
- Issue tracker: https://www.drupal.org/project/issues/klaro?version=3.0.0-rc1

## Maintainers

   * Sascha Meißner (sascha_meissner) - https://www.drupal.org/u/sascha_meissner
   * Jan Kellermann (jan kellermann) - https://www.drupal.org/u/jan-kellermann

