# UI Suite DaisyUI 5.0.x

A site-builder friendly DaisyUI theme for Drupal, using the UI Suite approach.

Use DaisyUI directly from Drupal backoffice (layout builder, manage display, views, blocks...).

## Requirements

This theme requires the following modules:

* UI Patterns 2
* UI Styles
* UI Skins
* UI Icons

This theme also requires the following libraries:

* Tailwind CSS 4
* DaisyUI 5
* Heroicons 2

This theme can be used with CDN hosted package of DaisyUI (version 5.0.0-beta.xxx) or by building your own package.

## DaisyUI library

### Default: Precompiled package of DaisyUI5

By default, the root theme uses a precompiled package of DaisyUI 5 as follows (inside `ui_suite_daisyui.libraries.yml` file):
```
daisyui:
  license:
    name: MIT
    url: https://github.com/saadeghi/daisyui/blob/v5/packages/daisyui/LICENSE
    gpl-compatible: false
  css:
    theme:
      "dist/css/app.css":
        { minified: true }

```

If you want to update / rebuild it, run these commands to generate the CSS files: 
- `npm install`
- `npm run build`

### Manually: use the starterkit provided in the theme to install Tailwind CSS, DaisyUI with Vite/NPM and build your own custom package 

Please follow instructions of UI Suite DaisyUI Starterkit under `starterkits/ui_suite_daisyui_starterkit` documented in [the README file](https://git.drupalcode.org/project/ui_suite_daisyui/-/blob/5.0.x/starterkits/ui_suite_daisyui_starterkit/README.md?ref_type=heads) 

## Icons library

### Herocions: use Asset Packagist

If you use the website Asset Packagist, the composer.json can be like:
```
{
    "require": {
        "composer/installers": "2.*",
        "oomphinc/composer-installers-extender": "2.*",
        "npm-asset/heroicons": "2.2.0"
    },
    "repositories": {
        "asset-packagist": {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },
        "drupal": {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        }
    },
    "extra": {
        "installer-paths": {
            "web/libraries/{$name}": [
                "type:drupal-library",
                "type:bower-asset",
                "type:npm-asset"
            ]
        },
        "installer-types": [
            "bower-asset",
            "npm-asset"
        ]
    }
}
```

## Theme installation

Install as you would normally install a contributed Drupal theme. For further information, see [Installing Drupal Themes](https://www.drupal.org/docs/extending-drupal/themes/installing-themes).

## Theme configuration

The theme has no menu or modifiable settings on its own.

Configuration is provided by the UI Suite ecosystem modules.

## Maintainers

Current maintainer:
* Michael Fanini: [G4MBINI](https://www.drupal.org/u/g4mbini)


Supporting organization:
* Dropteam: [official website](https://www.dropteam.fr/) - [d.org company page](https://www.drupal.org/dropteam)
