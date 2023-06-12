Table of contents
-----------------

* Introduction
* Features
* Requirements
* Configuration
* References
* Notes


Introduction
------------

The **Schema.org Blueprints Layout Paragraphs** provides integration with
the Layout Paragraphs module.


Features
--------

- Provides a 'Layout', 'block', and 'webform' paragraph type with JSON:API.
- Automatically adds and configures layout paragraphs field storage,
  instance, form display, and view display.
- Configure paragraph libraries support when the 'Layout Paragraphs Library'
  module is enabled.
- Enables 'style_options' for all paragraph types used in layout paragraphs.
- Customizes Quotation, Statement, Header, and ItemList paragraph output.
- Automatically adds types to the paragraph node's target bundles.

Requirements
------------

**[Layout Paragraphs](https://www.drupal.org/project/layout_paragraphs)**  
Field widget and formatter for using layouts with paragraph fields.

**[Style Options](https://www.drupal.org/project/style_options)**   
Provides configurable styles management for attaching various style plugins to Layouts and Paragraphs.


Configuration
-------------

- Go to the Schema.org types configuration page.  
  (/admin/config/search/schemadotorg/settings/types)
- Go to the 'Layout Paragraphs settings' details.
- Enter Schema.org types that default to using layout paragraphs.
- Enter the default paragraph types to be using with in layout paragraphs.
- Enter the themes that should enhance the layout paragraph component markup
  for Quotation, Statement, Header, and ItemList.


References
----------

- [Talking Drupal #337 - Layout Paragraphs](https://www.talkingdrupal.com/337)
- [Decoupling Acromedia.com with Drupal 9 & React](https://www.acromedia.com/article/decoupling-acromediacom-with-drupal-9-react)
- [Paragraphs vs Layout Builder in Drupal](https://www.mediacurrent.com/videos/paragraphs-vs-layout-builder-drupal)
- [Layout Paragraphs: A new way to manage Paragraphs](https://www.morpht.com/blog/layout-paragraphs-new-way-manage-paragraphs)


Notes
-----

- Icons are from [Font Awesome](https://fontawesome.com/)
