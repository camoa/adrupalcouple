Table of contents
-----------------

* Introduction
* Features
* Configuration
* Todo


Introduction
------------

The **Schema.org Blueprints Translation module** manages translations for 
Schema.org types and properties as they are created.

The module removes the manual task of enabling/disabling for entities 
and fields. For example, when a <http://schema.org/Recipe> is created, 
only the text fields needs to be translated, meanwhile the 
<http://schema.org/NutritionInformation> fields do not need to be translated.


Features
--------

- Automatically enables translation for Schema.org mapping entity types 
  and fields.

- Adds JSON-LD properties for https://schema.org/workTranslation 
  and https://schema.org/translationOfWork to translated nodes 
  mapped to CreativeWork.


Configuration
-------------

- Go to the Schema.org translations settings page
  (/admin/config/search/schemadotorg/settings/translations)
- Go to the 'Taxonomy settings' details.
- Enter Schema.org types that should never be translated.
- Enter Schema.org properties that should never be translated.
- Enter field names that should never be translated.
- Enter field types that should always be translated.


Todo
----

- Determine how best to translate the default English Schema.org type
  and property descriptions.
