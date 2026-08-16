## INTRODUCTION

The main Editoria11y module provides a simple "Export CSV" button on each dashboard View.

You only need this module and its batch export dependencies if your exports are exceeding your server timeout, or if you want to export custom Views.

Note that dependencies require manually installing league/csv if the module is not installed via composer.

<h3 id="module-project--features">Features</h3>
On install, this adds three filterable Views for Editoria11y pages with issues, recent results, and dismissals. These Views can be modified with additional filters and fields to fit your site.

If you edit these Views, be sure to edit both the Page display and the Data Export display in tandem, to make sure your filters are reflected in the export. And save a copy of your config, as future updates to this module may reset the default Views to their default configuration.

Like the main module's streaming export, cells that begin with a spreadsheet formula character (`=`, `-`, `+`, `@`, tab or carriage return) are prefixed with an apostrophe, so a formula planted in a page title or user name is displayed as text rather than executed when the CSV is opened in Excel or LibreOffice ([CSV injection](https://owasp.org/www-community/attacks/CSV_Injection)). Spreadsheet applications hide the apostrophe; scripts consuming the CSV will see it literally.

## Requirements

- Editoria11y 3.0.x
- [Views Data Export](https://www.drupal.org/project/views_data_export) with the `league/csv` library.

## Installing with Composer

Install Views Data Export with Composer so its PHP library dependency
(`league/csv`, via the CSV Serialization module) is resolved automatically:

    composer require drupal/views_data_export
    drush en editoria11y_export

Downloading the modules manually (e.g. from the "Download" tarball) does **not**
install `league/csv`, and CSV downloads will fail at the encoding step with
`Class "League\Csv\Writer" not found`.


## MAINTAINERS

- John Jameson (itmaybejj) - https://www.drupal.org/u/itmaybejj
