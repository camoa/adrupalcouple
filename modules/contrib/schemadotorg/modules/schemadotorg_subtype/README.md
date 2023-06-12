Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The **Schema.org Blueprints Subtype module** allows defining more specificity 
without creating dedicated entity types for every appropriate Schema.type.

For example, the subtypes for <http://schema.org/Event> are mainly for adding a 
little extra specificity about an Event. Most event subtypes do not need to 
have dedicated content types created.


Features
--------

- Adds Enable Schema.org subtyping to Schema.org mapping UI.
- Site builders can alter subtype field names, labels, descriptions, 
  and allowed values.
- Adds custom 'subtype' mapping to Schema.org mapping properties.
- Alters the Schema.org mapping list builder and adds a 'Subtype' column.


Configuration
-------------

- Go to the Schema.org types settings page
  (/admin/config/search/schemadotorg/settings/subtype)
- Go to the 'Schema.org subtyping' details.
- Enter default field suffix used for subtype field machine names.
- Enter default label used for subtype fields.
- Enter the default description used for subtype fields.
- Enter Schema.org types that support subtyping by default.
