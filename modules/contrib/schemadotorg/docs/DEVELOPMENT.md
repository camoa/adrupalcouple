Schema.org Blueprints Development
---------------------------------

**TLDR;** The Schema.org Blueprints modules provides hooks and several example
modules for integrating contributed modules into the Schema.org mapping UI
and JSON-LD building workflow.


# Notes

- The Schema.org Blueprints module relies more on dedicated sub-modules with
  configurable settings over plugins.

  For example, determining what Schema.org types and properties are available
  and their default settings is handled using YAML configuration files.
  @see [schemadotorg.settings.yml](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/config/install/schemadotorg.settings.yml)

- Using plugins for integrating Schema.org types and properties could create a
  scalability challenge because there 900+ Schema.org types and
  1500+ Schema.org properties.

- The admin UI for configuration setting is trying to be as simple as possible
  for managing a lot of configuration, while primarily targeting experienced
  site builders and developers.


# Hooks

- [schemadotorg/schemadotorg.api.php](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/schemadotorg.api.php)
  Provides hooks to alter mappings, entity types, and fields.

- [schemadotorg/modules/schemadotorg_jsonld/schemadotorg_jsonld.api.php](https://git.drupalcode.org/project/schemadotorg/-/blob/1.0.x/modules/schemadotorg_jsonld/schemadotorg_jsonld.api.php)
  Provides hooks to define and alter Schema.org JSON-LD.


# Example modules

- **[Schema.org Blueprints Flex Field](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_flexfield)**  
  Allows a Flex field to be used to create Schema.org relationships within an
  entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Inline Entity Form](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_inline_entity_form)**  
  Allows an inline entity form to be automatically added to Schema.org
  properties within an entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Paragraphs](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_paragraphs)**  
  Integrates the Paragraphs and Paragraphs Library module with the Schema.org
  Blueprints module.

- **[Schema.org Blueprints Smart Date](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_smart_date)**  
  Allows a Smart date field to be used to create date ranges and event schedules included in a site's Schema.org JSON-LD.

# Integration process

- Determine how and where the contributed module should be integrated.
  - Does the contributed module provide a new entity type, field type, field widget,
    or field display?

- Provide basic configuration integration.
  - Can the contributed module be integrated using existing configuration settings?

- Create a Schema.org Blueprints integration module.
  - Can you use schemadotorg_{module_name} as the integration module's
    namespace?

- Define the contributed module's configuration settings
  - Does the contributed module need to alter the default configuration settings?
  - Does the contributed module need to provide configuration settings?

- Add test coverage
  - Can you extend the Schema.org Blueprints base test classes?
  - Can you extend the Schema.org Blueprints test trait?
  - Can you copy exists Schema.org Blueprints tests?
