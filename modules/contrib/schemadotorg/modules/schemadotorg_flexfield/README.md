Table of contents
-----------------

* Introduction
* Features
* Configuration
* Notes
* References


Introduction
------------

The **Schema.org Blueprints Flexfield module** allows a Flex field to be used to
create Schema.org relationships within an entity type/bundle Schema.org mapping.


Features
--------

- Alter the Schema.org properties configuration form to allow site builders
  to determine which properties should be mapped to a flexfield instead of
  Schema.org type.
- Appends units field suffix to flexfield widget edit forms and
  flexfield view displays.


Requirements
------------

**[FlexField](https://www.drupal.org/project/flexfield)**  
Defines a new "FlexField" field type that lets you create simple inline multiple-value fields without having to use entity references.


Configuration
-------------

- Go to the Schema.org properties configuration page.  
  (/admin/config/search/schemadotorg/settings/properties)
- Go to the 'Flex Field settings' details.
- Set Schema.org properties that should use flexfields and define the
  flexfield item data types.


Notes
-----

The **Schema.org Blueprints Flexfield module** is intended as proof of concept of
alternate way to manage relationships between Schema.org types.

This module may be removed before the first beta release and moved to
a sandbox module.


References
----------

- [Baking a Recipe using the Schema.org Blueprints module for Drupal](https://www.jrockowitz.com/blog/schemadotorg-recipe)
