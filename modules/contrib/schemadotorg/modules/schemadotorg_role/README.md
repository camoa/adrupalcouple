Table of contents
-----------------

* Introduction
* Features
* Configuration
* Requirements

Introduction
------------

The **Schema.org Blueprints Role** module manages roles 
(https://schema.org/role) for Schema.org propertis.


Features
--------

- Allows dedicated role fields to be created with Schema.org type.
- Adds role field values to JSON-LD property.
- Exposes role fields to JSON:API.
- Use Entity Reference Override fields for role related fields.


Configuration
-------------

- Go to the Schema.org properties configuration page.  
  (/admin/config/search/schemadotorg/settings/properties)
- Go to the 'Role settings' details.
- Enter role field definitions which will be available to Schema.org properties.
- Enter Schema.org properties and their roles.
- Enter the Schema.org properties that should should use the Entity Reference 
  Override field to capture an entity references roles.


Requirements
------------

- **[Entity Reference Override](https://www.drupal.org/project/entity_reference_override)**  
  Provides entity reference field with overridable label.


Todo
----

- [Issue #2822973: Add entity_browser support to Entity Reference Override](https://www.drupal.org/project/entity_reference_override/issues/2822973)
  
