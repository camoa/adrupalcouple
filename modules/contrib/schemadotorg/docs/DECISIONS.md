Schema.org Blueprints Architecture Decisions Records (ADRs)
-----------------------------------------------------------

# 1000 - Coding standards & best practices

0000 - Use minimal ADRs to track architecture decisions.
- Some documentation is better than no documentation
- Self-explanatory decisions can be explained via the use case and solution

0000 - Follow [Drupal.org's coding standards](https://www.drupal.org/docs/develop/standards)
- Provide interfaces for all services
- Maintain README.md for sub-modules

0000 - Write basic tests for all functionality
- Some test coverage is better than no test coverage
- Public methods for services/interfaces should have test coverage

0000 - Write full tests for regressions

0000 - Use kernel tests for verifying generated content models and JSON-LD.
- @see \Drupal\Tests\schemadotorg\Kernel\SchemaDotOrgKernelEntityTestBase

0000 - Organize and name fields according to their use case
- Structured data (Schema.org) fields are prefixed with schema_*
- General (custom) fields are prefixed with field_*

0000 - Use config snapshot test to confirm an expected configuration for starter kits
- @see \Drupal\Tests\schemadotorg\Functional\SchemaDotOrgConfigSnapshotTestBase

0000 - Form elements should include a title and description that states the element's intent and usage


# 2000 - Code architecture & best practices

0000 - Namespace everything with schemadotorg_* or SchemaDotOrg* prefix
- Ensures all Schema.org code is searchable and identifiable. 

0000 - Use loosely coupled sub-modules that do one thing over monolithic modules doing many things

0000 - Use sub-modules for integration with other modules and sub-systems

0000 - Use sub-modules to support distinct Schema.org features
- Features include [https://schema.org/Role](https://schema.org/Role), [https://schema.org/identifier](https://schema.org/identifier), and sub-typing

0000 - Create dedicated projects for integration with other ecosystems

0000 - Use hooks for simple and basic integrations on a contributed module's behalf
- @see schemadotorg.schemadotorg.inc

0000 - Rely on the default settings for field storage, instance, view, and form displays whenever possible
- Generally, only alter the default configuration when it improves the UI/UX

0000 - Provide reasonable configuration defaults

0000 - Ensure that most settings and behaviors are configurable or alterable via hooks

0000 - Use contributed modules and configuration before writing custom code 

0000 - During Alpha releases only support the latest stable release of Drupal core.
- TBD What versions of Drupal core should be supported.

0000 - Always use patch files uploaded to issues on Drupal.org. 
- Do NOT use an MR's diff as a patch because contents may change.
- @see https://www.drupal.org/docs/develop/git/using-gitlab-to-contribute-to-drupal/downloading-a-patch-file


# 3000- Schema.org

0000 - Schema.org should provide 80% of a site's base content architecture and the remaining 20% is custom configuration and code

0000 - Examples from Schema.org should be considered the canonical reference for implementation guidelines

0000 - Inverse of relationships
- subOrganization ↔ parentOrganization: Used to build Organization hierarchy
- memberOf ↔ member: Used to associate a Person with a (conceptual) Organization
- worksFor ↔ employee: Used to associate a Person with a (physical) LocalBusiness
- subjectOf ↔ about: Used to associate a Thing with a CreativeWork
- isPartOf ↔ hasPart: Used to build CreativeWork parent/child relationships and hierarchies 
- containedInPlace ↔ containsPlace: Used to associate Place within a Place.


# 4000 - User experience

0000 - Provide a demo of the ideal content authoring experience that can be created via Schema.org Blueprints

0000 - Content authoring experience takes priority over developer experience

0000 - Use the main node edit form for structured Schema.org data and the sidebar for meta and configuration data


# 5000 - Dependency management

0000 - Use Drupal core's recommendation for organizing composer.json
- [Issue #2769841: Prefer caret over tilde in composer.json ](https://www.drupal.org/project/drupal/issues/2769841)

0000 - Use composer.libaries.json with the composer merge plugin for dependencies and patches

0000 - Use composer.patches.VERSION.json for Drupal version specific patches


# 6000 - JSON:API

0000 - Only expose required and useful endpoints and properties
- Expose all Schema.org types and properties to APIs
- Expose standard and expected entity types and properties to APIs

0000 - When feasible hide Drupalisms from endpoints and properties
- Remove field_- prefix from properties

0000 - Avoid deleting API endpoints or properties. Instead, deprecate API endpoints and properties as needed.
- @see [GraphQL Best Practices](https://graphql.org/learn/best-practices/#versioning)

0000 - Use snake case for API entities and properties.
- Aligns with Drupal's naming conventions


# 7000 - StarterKit and Demo

0000 - Provide starter kits for common sets of Schema.org types with additional functionality.

0000 - Allows starter kits to add fields to existing Schema.org types.

0000 - Provide a demo profile and module that creates the ideal backend content management and authoring experience


# 8000 - Contributed modules and themes

⭐ = Indicated that the Schema.org Blueprints module provides an integration/sub-module.

0000 - Follow contributed module selection best practices
- Select popular and stable modules when possible.
- Look at popular and supported distributions for suggestions.
- Choose modules that address clearly defined goals of the backend and front-end user experience.

0000 - Consistently name entity fields using (field|schema)_{bundle}_{name} or (field|schema)_{name}

0000 - Use the 'schema_- field prefix to distinguish Schema.org properties from other fields.

0000 - Use field-related modules that structure and manage [https://schema.org/DataType](https://schema.org/DataType) and [https://schema.org/Intangible](https://schema.org/Intangible).
- [Address](https://www.drupal.org/project/address) ⭐ for [https://schema.org/address](https://schema.org/address)
- [Corresponding Entity References](https://www.drupal.org/project/cer) ⭐ for [https://schema.org/inverseOf](https://schema.org/inverseOf)
- [Duration Field](https://www.drupal.org/project/duration_field) for [https://schema.org/Duration](https://schema.org/Duration)
- [Field Validation](https://www.drupal.org/project/field_validation) ⭐ for [https://schema.org/identifier](https://schema.org/identifier)
- [Gender](https://www.drupal.org/project/gender) for [https://schema.org/GenderType](https://schema.org/GenderType)
- [Geolocation Field](https://www.drupal.org/project/geolocation) ⭐ for [https://schema.org/GeoCoordinates](https://schema.org/GeoCoordinates).
  - [Geofield](https://www.drupal.org/project/geofield) good alternative for [https://schema.org/GeoCoordinates](https://schema.org/GeoCoordinates). No integration is being provided.
- [Office Hours](https://www.drupal.org/project/office_hours) ⭐ for [https://schema.org/OpeningHoursSpecification](https://schema.org/OpeningHoursSpecification)
- [Range](https://www.drupal.org/project/range) for [https://schema.org/MonetaryAmount](https://schema.org/MonetaryAmount)
- [SmartDate](https://www.drupal.org/project/smart_date) ⭐ for [https://schema.org/Date](https://schema.org/Date) and [https://schema.org/Schedule](https://schema.org/Schedule)
- [Time Field](https://www.drupal.org/project/time_field) for [https://schema.org/Time](https://schema.org/Time)

0000 - Use entity reference-related modules for relationships
- [Entity Embed](https://www.drupal.org/project/entity_embed) for building complex structured body content
- [Existing Values Autocomplete Widget](https://www.drupal.org/project/existing_values_autocomplete_widget) for text fields with common values
- [Entity Reference Override](https://www.drupal.org/project/entity_reference_override) for https://schema.org/Role relationships.
- [Entity Reference Tree Widget](https://www.drupal.org/project/entity_reference_tree) for selecting hierarchical taxonomy terms
- [Inline Entity Form](https://www.drupal.org/project/inline_entity_form) ⭐ for editing concrete and key relations
- [Content Browser](https://www.drupal.org/project/content_browser) module for browsing and selecting content

0000 - Use common SEO modules to improve SEO
- [Simple XML sitemap](https://www.drupal.org/project/simple_sitemap) ⭐for generating sitemap.xml
- [Metatag](https://www.drupal.org/project/metatag) ⭐for providing meta tag and data support

0000 - For Demo & StarterKits: Use config management module as needed
- [Configuration Rewrite](https://www.drupal.org/project/config_rewrite) for tweaking existing configuration settings

0000 - For Demo & StarterKits: Use site builder tools as needed
- [Automatic Entity Label](https://www.drupal.org/project/auto_entitylabel) ⭐for computed entity labels for [https://schema.org/Person](https://schema.org/Person)
- [Convert Bundles](https://www.drupal.org/project/convert_bundles) for convert Schema.org types to more specific types.
- [Focal Point](https://www.drupal.org/project/focal_point) ⭐for automated cropping of images
- [Field Group](https://www.drupal.org/project/field_group) ⭐for grouping related fields
- [Entity Clone](https://www.drupal.org/project/entity_clone) for cloning entities
- [Entity Prepopulate](https://www.drupal.org/project/epp) for prepopulating entity reference via query string parameters.
- [Entity Print](https://www.drupal.org/project/entity_print) for printing entities as PDF documents
- [EVA: Entity Views Attachment](https://www.drupal.org/project/eva) for attaching a view to an entity's display
- [Linkit](https://www.drupal.org/project/linkit) for managing internal links
- [Token Filter](https://www.drupal.org/project/token_filter) for allowing tokens to be used within a text format
- [Views Add Button](https://www.drupal.org/project/views_add_button) for including add content buttons to an admin view.
  - [Add Content by Bundle Views Area Plugin ](https://www.drupal.org/project/add_content_by_bundle)

0000 - For Demo & StarterKits: Use content authoring UX/UI improvement modules as needed
- [Allowed Formats](https://www.drupal.org/project/allowed_formats) for limiting and simplifying text formats
- [Autosave Form](https://www.drupal.org/project/autosave_form) preventing editors from losing data
- [Chosen](https://www.drupal.org/project/chosen) improving multi-select UX
- [CKEditor Paste Filter](https://www.drupal.org/project/ckeditor_paste_filter) for cleaning up text pasted from MS Word
- [DropzoneJS](https://www.drupal.org/project/dropzonejs) for drag-n-drop file uploads
- [Multiple Fields Remove Button](https://www.drupal.org/project/multiple_fields_remove_button) for removing multiple field values
- [Node Edit Protection](https://www.drupal.org/project/node_edit_protection) for preventing editors from losing data
- [Same Page Preview](https://www.drupal.org/project/same_page_preview) to allows editors to preview changes on the same page

0000 - For Demo: Use administration improvement modules as needed.
- [Admin Toolbar Language Switcher](https://www.drupal.org/project/toolbar_language_switcher) for switching languages via the Gin Admin theme
- [Content Model Documentation](https://www.drupal.org/project/content_model_documentation) for displaying entity relationship diagrams (ERD)
- [Dashboards with Layout Builder](https://www.drupal.org/project/dashboards) for providing customizable dashboards to users
- [Environment Indicator](https://www.drupal.org/project/environment_indicator) for displaying the current environment to administrators
- [Type Tray](https://www.drupal.org/project/type_tray) for improving the 'Add content' UI/UX
- [Login Destination](https://www.drupal.org/project/login_destination) redirect authenticated users to the appropriate dashboard

0000 - For Demo: Use scheduling and content moderation modules as needed
- [Moderation Dashboard](https://www.drupal.org/project/moderation_dashboard) for providing a moderation state dashboard
- [Moderation Sidebar](https://www.drupal.org/project/moderation_sidebar) for quick access to an entity's moderation state
- [Revision Log Default](https://www.drupal.org/project/revision_log_default) for providing default log messages
- [Scheduler](https://www.drupal.org/project/scheduler) ⭐for scheduling publish and unpublish dates

0000 - For Demo: Use the [Gin Admin Theme](https://www.drupal.org/project/gin) for the administrative UI/UX
- [Gin Layout Builder](https://www.drupal.org/project/gin_lb) for layout builder
- [Gin Login](https://www.drupal.org/project/gin_login) for customizing the user login
- [Gin Toolbar ](https://www.drupal.org/project/gin_toolbar)for admin toolbar enhancements

0000 - Use the [Custom Field](https://www.drupal.org/project/custom_field) ⭐module for simple key/value pairs. 
(i.e. [https://schema.org/nutrition](https://schema.org/nutrition))
- @todo Write complete ADR
- Alternatives: [Data field](https://www.drupal.org/project/datafield) and [FlexField](https://www.drupal.org/project/flexfield)

0000 - Use the [Paragraphs](https://www.drupal.org/project/paragraphs) ⭐ module for complex data.  
(i.e. [https://schema.org/HowTo](https://schema.org/HowTo))
- @todo Write complete ADR

0000 - Use the [Layout Paragraphs](https://www.drupal.org/project/layout_paragraphs) ⭐ module for structured data layout
- @todo Write complete ADR
- Drupal Core's Layout Builder does not provide easily structured data
