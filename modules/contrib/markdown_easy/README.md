## Introduction

The Markdown Easy module is a Drupal text filter to convert Markdown into HTML.

The primary use case for this module is to quickly and easily be able to
configure and utilize a Markdown text filter. This module utilizes the
[league/commonmark Markdown parser library](https://commonmark.thephpleague.com)
and allows for the bare minimum configuration in the Drupal admin interface.

It is required to utilize the "Limit allowed HTML tags and correct
faulty HTML" Drupal core text filter in conjunction with this module.

## Requirements

None (other than the desire to convert Markdown to HTML!)

## Installation

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## Configuration

### Automatic

- A new "Markdown" text format is created when Markdown Easy is enabled. This
text format may be customized as desired.

### Manual

If you do not want to use the "Markdown" text format that is automatically
created when Markdown Easy is enabled, then use the following steps as a
starting point to create or modify your own text format:
- Add the Markdown Easy text filter to any text format.
- Select your preferred "flavor" of Markdown in the text filter's
settings on the text format's configuration page:
  - "Standard Markdown" provides the most standard Markdown features supported
    by CommonMark.
  - "GitHub-flavored Markdown" includes the following extensions: Autolinks,
    Disallowed Raw HTML, Strikethrough, Tables, and Task Lists.
  - "Markdown Smörgåsbord" includes everything from GitHub-flavored Markdown
    plus the following extensions: Footnotes, Description lists.
- IMPORTANT - Enable and configure the "Limit allowed HTML tags and correct
faulty HTML" filter to run after the Markdown Easy filter. Ensure that `<p>` and
`<br>` tags are included in the list of allowed tags. Without this step, the
text format will allow all HTML tags.
- Markdown Easy requires (via validation) that it be configured with the "Limit
allowed HTML tags and correct faulty HTML" filters enabled to run after Markdown
Easy. This can be overridden (at your peril) by removing the validation handler.
See tests/modules/markdown_easy_test/markdown_easy_test.module for an example.

### Upgrading from Markdown Easy 1.x to 2.x

Markdown Easy 1.x required the "Convert line breaks into HTML" filter be
enabled. This is no longer recommended, and you will receive a warning if you
save a text format including "Markdown Easy" and "Convert line breaks into
HTML". When upgrading to 2.x, it is recommended to:

- Disable the "Convert line breaks into HTML" filter.
- Add `<p>` and `<br>` to the allowed tags in the "Limit allowed HTML tags and
correct faulty HTML" filter.

Note that version 1.x did not handle line breaks in accordance with the Markdown
spec, so if your site depends on this behavior you may want to retain the 1.x
configuration.

## Additional information
- The Markdown Easy text filter is configured to run with the following
security-related settings by default:
  - html_input: strip
  - allow_unsafe_links: false
- See https://commonmark.thephpleague.com/2.4/security/ for more info. To
override these settings, or to customize the configuration of the Markdown
processor, utilize hook_markdown_easy_config_modify().
- If you want to add additional Markdown extensions that one of the included
"flavor" options doesn't offer (https://commonmark.thephpleague.com/2.6/extensions/overview/),
utilize hook_markdown_easy_environment_modify().
- If you want to disable all Markdown Easy validations (including for the "Limit
allowed HTML") without implementing one of the Markdown Easy hooks, see the
"Advanced configuration" section of the documentation (link below.)
- If you want to allow HTML tags to pass through the Markdown processor without
implementing one of the Markdown Easy hooks, see the "Advanced configuration"
section of the documentation (link below.)
- Note that the Markdown Easy module is currently not compatible with the
Smart Trim module.

## Tip for migrating from Markdown module
- The [Markdown module](https://www.drupal.org/project/markdown) allows the
site-builder to install multiple Markdown processors, therefore, prior to
installing Markdown Easy, if the league/commonmark parser is already installed,
it should be removed (as Markdown Easy uses the latest version and Markdown does
not).

composer remove league/commonmark

## Documentation
- Full documentation at https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/markdown-easy

## Maintainers

Current maintainers:

- Michael Anello (ultimike) - https://www.drupal.org/u/ultimike
