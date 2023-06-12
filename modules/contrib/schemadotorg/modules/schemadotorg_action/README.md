Table of contents
-----------------

* Introduction
* Features
* Requirements
* References
* Notes
* Todo


Introduction
------------

The **Schema.org Blueprints Action** provides support for 
<https:://schema.org/Action> using a call to action paragraph type.


Features
--------

- Creates a 'Call to action' paragraph type which is mapped to
  [Webpage](https://schema.org/WebPage).
- Sets JSON-LD <https://schema.org/potentialAction> property for links using 
  the custom 'schema_potential_action' link attribute.


Requirements
------------

**[Link Attributes](https://www.drupal.org/project/link_attributes)**  
Provides a widget to allow settings of link attributes for menu links.


References
----------

- [Schema.org/Actions](https://schema.org/Action)
- [Schema.org/ConsumeActions](https://schema.org/ConsumeAction)
- [Schema.org Actions](https://www.seroundtable.com/schema-actions-18438.html)
- [Actions in schema.org](https://www.w3.org/wiki/images/2/25/Schemaorg-actions-draft5.pdf)
- [What is an “Action” in Schema?](https://ondyr.com/what-is-action-schema/)
- [How to add article schema markup to blog posts](https://www.hallaminternet.com/introducing-schema-org-action-markups/?amp)

I am interested in:
    '#options':
      Preparing for an appointment: Preparing for an appointment
      Seeking general one-on-one support: Seeking general one-on-one support
      Seeking Resources: Seeking Resources
      Support on how to cope with cancer and having to parent young children: Support on how to cope with cancer and having to parent young children
      Quality of life after cancer: Quality of life after cancer
      Speaking with someone about Progression of disease: Speaking with someone about Progression of disease
    

Notes
-----

- CTA = <https://schema.org/WebPage> + <https://schema.org/Action>  
  = <https://schema.org/potentialAction>

Todo
----

- Replace custom 'class' field with
  [Paragraphs Collection](https://www.drupal.org/project/paragraphs_collection) module.
  @see schemadotorg_layout_paragraphs_paragraph_view_alter()
