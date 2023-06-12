Table of contents
-----------------

* Introduction
* Requirements
* Installation
* Configuration


Introduction
------------

> The **Schema.org Blueprints module** provides perfect data structures (Schema.org),
> pristine APIs (JSON:API), and great SEO (JSON-LD).

The [Schema.org Blueprints](https://www.drupal.org/project/schemadotorg) module
uses [Schema.org](https://schema.org) as the blueprint for a Drupal website's
content architecture and structured data.

The best way to get started using the Schema.org Blueprints module is to read
about [Schema.org](https://schema.org) and browse the available
[schemas](https://schema.org/docs/schemas.html).

Once you understand Schema.org, please watch a
[short overview](https://youtu.be/XkZP6QjJkWs) or
[full demo](https://youtu.be/_kk97O1SEw0) of the Schema.org Blueprints module.

Additional documentation

- [docs/NOTES.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/NOTES.md)
- [docs/ROADMAP.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/ROADMAP.md)
- [docs/FEATURES.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/FEATURES.md)
- [docs/MODULES.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/MODULES.md)
- [docs/DRUSH.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/DRUSH.md)
- [docs/DEVELOPMENT.md](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/docs/DEVELOPMENT.md)


Features
--------

- Installs Schema.org CSV into Drupal
- Exposes Schema.org types and properties to Drupal modules
- Defines Schema.org mapping and mapping type entities
- Allow Schema.org types, properties, and names to be configured
- Build entity types and fields from Schema.org types and properties
- Ensure that Schema.org naming conventions work with Drupal's internal
  naming conventions
- Provides Drush commands to create and delete Schema.org mappings


Requirements
------------

This module requires the Field, Text, and Options modules included with
Drupal core.


Installation
------------

Install the Schema.org Blueprints module as you would normally
[install a contributed Drupal module](https://www.drupal.org/node/1897420).


Configuration
-------------

- Configure 'Schema.org Blueprints' administer permission.  
  (/admin/people/permissions/module/schemadotorg)

- Review Schema.org types configuration.  
  (/admin/config/search/schemadotorg/settings/types)

- Review Schema.org properties configuration.  
  (/admin/config/search/schemadotorg/settings/properties)

- Review Schema.org naming conventions configuration.  
  (/admin/config/search/schemadotorg/settings/names)

- Review Schema.org mappings.  
  (/admin/config/search/schemadotorg)

- Review Schema.org mapping types.  
  (/admin/config/search/schemadotorg/types)
