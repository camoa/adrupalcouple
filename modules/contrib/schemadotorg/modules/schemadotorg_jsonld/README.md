Table of contents
-----------------

* Introduction
* Features
* Configuration


Introduction
------------

The **Schema.org Blueprints JSON-LD module** builds and adds Schema.org structured 
data as JSON-LD in the head of web pages.


Features
--------

- Adds 'application/ld+json' to the <head> section of HTML pages.
- Converts the Address field values to https://schema.org/PostalAddress
- Apply image styles to image files shared via JSON-LD.
- Converts Drupal entities to Schema.org types for JSON-LD.
- Provide hooks for modules to define and alter JSON-LD for routes, 
  types, and properties.


Configuration
-------------

- Go to the Schema.org JSON:LD configuration page.  
  (/admin/config/search/schemadotorg/settings/jsonld)
- Go to the 'JSON-LD settings' details.
- Enter the default Schema.org property order.
- Enter the Schema.org property image styles.
