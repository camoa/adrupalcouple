Schema.org Blueprints: Recommended core and contributed modules
---------------------------------------------------------------

Below are recommended core and contributed modules that can be used with the Schema.org Blueprints module.

## Sub-modules

_Below modules are included with the Schema.org Blueprints module/package._

### Base

The below base modules are required to understand and build entity types based
on Schema.org types.

- **[Schema.org Blueprints](https://git.drupalcode.org/project/schemadotorg)**  
  Uses Schema.org as the blueprint for the content architecture and structured data in a Drupal website.

- **[Schema.org Blueprints UI](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_ui)**  
  Allows administrators to attach custom Schema.org properties to fieldable types.

- **[Schema.org Blueprints Report](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_report)**  
  Provides a report for browsing Schema.org types, properties, and naming conventions.

- **[Schema.org Blueprints Help](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_help)**  
  Provides a dedicated help section for the Schema.org Blueprints module and submodules.

### UI/UX

The below UI/UX modules help enhance the creation and management
Schema.org types.

- **[Schema.org Blueprints Descriptions](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_descriptions)**  
  Sets entity type and field descriptions to their corresponding Schema.org type and property comments.

- **[Schema.org Blueprints Export](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_export)**  
  Provides a CSV export of Schema.org type mappings.

- **[Schema.org Blueprints Identifier](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_identifier)**  
  Manages identifiers (https://schema.org/identifier) for Schema.org types.

- **[Schema.org Blueprints Mapping Set](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_mapping_set)**  
  Provides the ability to create a set of related Schema.org types via Drush.

- **[Schema.org Blueprints Role](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_role)**    
  Manages roles (https://schema.org/Role) for Schema.org properties.

- **[Schema.org Blueprints Subtype](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_subtype)**  
  Subtypes allow more specificity without having to create dedicated entity types for every appropriate Schema.type.

- **[Schema.org Blueprints Translation](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_translation)**  
  Manages translations for Schema.org types and properties as they are created.

### Fields

The below field related modules help configure entity edit forms
and field configuration settings.

- **[Schema.org Blueprints Address](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_address)**  
  Allows an address field to be used to create a http://schema.org/PostalAddress.

- **[Schema.org Blueprints Allowed Formats](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_allowed_formats)**  
  Sets allowed format for Schema.org properties within an entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Automatic Entity Labels](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_auto_entitylabel)**  
  Creates automatic entity label settings for Schema.org types.

- **[Schema.org Blueprints Corresponding Entity References](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/cer)**
  Improves https://inverseOf support using the Corresponding Entity References module.

- **[Schema.org Blueprints Existing Values Autocomplete Widget](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_existing_values_autocomplete_widget)**  
  Allows an autocomplete widget to be automatically added to Schema.org properties within an entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Field Group](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_field_group)**  
  Creates field groups in entity view and form displays when Schema.org properties are mapping to a field.

- **[Schema.org Blueprints Field Validation](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_field_validation)**  
  Adds field validation/constraint rules to Schema.org properties.

- **[Schema.org Blueprints Flex Field](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_flexfield)**  
  Allows a Flex field to be used to create Schema.org relationships within an entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Focal Point](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_focal_point)**  
  Enables focal point for Schema.org images and generates default image styles for decoupled front-ends.

- **[Schema.org Blueprints Geolocation Field](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_geolocation)**  
  Allows a geolocation field to be used to create https://schema.org/GeoCoordinates.

- **[Schema.org Blueprints Inline Entity Form](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_inline_entity_form)**  
  Allows an inline entity form to be automatically added to Schema.org properties within an entity type/bundle Schema.org mapping.

- **[Schema.org Blueprints Office Hours](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_office_hours)**  
  Allows an Office hours field to be used to create https://schema.org/OpeningHoursSpecification.

- **[Schema.org Blueprints Options](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_options)**  
  Set allowed values for list (options) fields.

- **[Schema.org Blueprints Editorial](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_editorial)**  
  Adds an 'Editorial information' paragraph to the sidebar on every node edit form.

- **[Schema.org Blueprints Smart Date](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_smart_date)**  
  Allows a Smart date field to be used to create date ranges and event schedules included in a site's Schema.org JSON-LD.

### Entities

The below entity type related module provide support and mappings for
common entity types.

- **[Schema.org Blueprints Media](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_media)**  
  Integrates the Media and Media Library module with the Schema.org Blueprints module.

- **[Schema.org Blueprints Paragraphs](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_paragraphs)**  
  Integrates the Paragraphs and Paragraphs Library module with the Schema.org Blueprints module.

- **[Schema.org Blueprints Taxonomy](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_taxonomy)**  
  Assists with creating and mapping taxonomy vocabularies and terms.

### Layout

The below layout related modules provide support for building layouts using
Layout Paragraphs based on Schema.org types.

- **[Schema.org Blueprints Action](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_action)**  
  Provides support for https:://schema.org/Action using a call to action paragraph type.

- **[Schema.org Layout Paragraphs](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_layout_paragraphs)**  
  Provides integration with the Layout Paragraphs module.

- **[Style Options](https://www.drupal.org/project/style_options)**  
  Provides configurable styles management for attaching various style plugins to Layouts and Paragraphs.

### SEO

The below SEO related modules help support common and popular Drupal
SEO modules.

- **[Schema.org Blueprints Metatag](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_metatag)**  
  Automatically adds a metatag field to Schema.org types.

- **[Schema.org Blueprints Simple Sitemap](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_simple_sitemap)**  
  Automatically adds Schema.org types to sitemap.xml.

### Workflow

The below workflow related modules help support common and popular Drupal
workflow modules.

- **[Schema.org Blueprints Content Moderation](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_content_moderation)**  
  Automatically enables content moderation for Schema.org types as they are created.

- **[Schema.org Blueprints Scheduler](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_scheduler)**  
  Automatically enables scheduling for Schema.org types as they are created.

### JSON-LD

The below JSON-LD related modules generates Schema.org JSON-LD
for search engines.

- **[Schema.org Blueprints JSON-LD](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld)**  
  Adds Schema.org structured data as JSON-LD in the head of web pages.

- **[Schema.org Blueprints JSON-LD Custom](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld_custom)**  
  Allows custom JSON-LD to be added to Schema.org mapping.

- **[Schema.org Blueprints JSON-LD Breadcrumb](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld_breadcrumb)**  
  Add Drupal's breadcrumb to Schema.org JSON-LD for the current route.

- **[Schema.org Blueprints JSON-LD Embed](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld_embed)**  
  Extracts embedded media and content from an entity and includes the associated Schema.org type in JSON-LD.

- **[Schema.org Blueprints JSON-LD Endpoint](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld_endpoint)**  
  Provides an endpoint to get an entity's Schema.org JSON-LD.

- **[Schema.org Blueprints JSON-LD Preview](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonld_preview)**  
  Allows users to preview a web page's Schema.org JSON-LD.

### API

The below API related modules creates APIs based on Schema.org types

- **[Schema.org Blueprints JSON:API](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonapi)**  
  Builds on top of the JSON:API and JSON:API extras modules to apply Schema.org type and property mappings to JSON:API resources.

- **[Schema.org Blueprints JSON:API Preview](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_jsonapi_preivew)**  
  Allows users to preview a web page's Schema.org JSON:API.

### Next.js

The below Next.js related modules helps front-end developers build and maintain
a Next.js front-end.

- **[Schema.org Blueprints Next.js](https://git.drupalcode.org/project/schemadotorg_next/-/tree/1.0.x)**  
  Assists with the integration of Schema.org Blueprints with Next.js.

- **[Schema.org Blueprints Next.js Components](https://git.drupalcode.org/project/schemadotorg_next/-/tree/1.0.x/modules/schemadotorg_next_components)**  
  Generates and previews Next.js React components to assist with the integration of Schema.org Blueprints with Next.js

### Development

- **[Schema.org Blueprints Devel](https://git.drupalcode.org/project/schemadotorg/-/tree/1.0.x/modules/schemadotorg_devel)**  
  Provides development tools for the Schema.org Blueprints module.

### Demo

- **[Schema.org Blueprints Demo](https://git.drupalcode.org/project/schemadotorg_demo/-/tree/1.0.x/README.md)**  
  Provides an opinionated demo of the Schema.org Blueprints built on top of Drupal's standard profile with default content and translations.

## Core Modules

_Below are recommended Drupal core modules._

- **[Datetime](https://www.drupal.org/docs/8/core/modules/datetime)**  
  (Applies to: [Date](https://schema.org/Date), [DateTime](https://schema.org/DateTime))  
  Defines datetime form elements and a datetime field type.

- **[Link](https://www.drupal.org/docs/8/core/modules/link)**  
  (Applies to: [URL](https://schema.org/URL))  
  Provides a simple link field type.

- **[Media](https://www.drupal.org/docs/8/core/modules/media)**  
  (Applies to: [MediaObject](https://schema.org/MediaObject))  
  Manages the creation, configuration, and display of media items.

- **[Media Library](https://www.drupal.org/docs/8/core/modules/media_library)**  
  (Applies to: [MediaObject](https://schema.org/MediaObject))  
  Enhances the media list with additional features to more easily find and use existing media items.

- **[Telephone](https://www.drupal.org/docs/8/core/modules/telephone)**  
  (Applies to: [telephone](https://schema.org/telephone))  
  Defines a field type for telephone numbers.

## Contribute Modules

_Below are recommended contributed modules._

### UI/UX

_The below modules improve the Schema.org Blueprints module's user experience._

- **[Field Group](https://www.drupal.org/project/field_group)**  
  Provides the ability to group your fields on both form and display.

- **[Entity Browser](https://www.drupal.org/project/entity_browser)**  
  Provide a generic entity browser/picker/selector.

- **[Content Browser](https://www.drupal.org/project/content_browser)**  
  Provides a default Entity Browser for default Content Entity types, using Masonry.

- **[Autosave Form](https://www.drupal.org/project/autosave_form)**  
  Provides an autosave feature for all forms (entity forms inclusive).

- **[Node Edit Protection](https://www.drupal.org/project/node_edit_protection)**  
  Alerts a user if they are navigating away from a modified Node Edit form without saving.

### Workflow/Moderation

_The below modules improve the Schema.org Blueprints module's workflow and moderation experience._

- **[Moderation Dashboard](https://www.drupal.org/project/moderation_dashboard)**  
  Provides a per-user dashboard that contains useful blocks related to managing moderated content.

- **[Moderation Sidebar](https://www.drupal.org/project/moderation_sidebar)**  
  Provides an off-canvas menu to moderate the current Entity.

- **[Scheduler](https://www.drupal.org/project/scheduler)**  
  Gives content editors the ability to schedule nodes to be published and unpublished at specified dates and times in the future.

- **[Scheduler content moderation integration](https://www.drupal.org/project/scheduler_content_moderation_integration)**  
  A submodule for the scheduler module to integrate with content moderation.

### Field Collections

_The below modules provide different ways to create and manage a collection of fields._

- **[Paragraphs](https://www.drupal.org/project/paragraphs)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Enables the creation of paragraphs (i.e. component) entities.

- **[Entity Construction Kit (ECK)](https://www.drupal.org/project/eck)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  ECK (Entity Construction Kit) allows users to create and administer entity types, bundles and entities from UI.

- **[Micro-content](https://www.drupal.org/project/microcontent)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Provides a lightweight reusable content-entity and type that supports fields, revisions and translation.

- **[Storage](https://www.drupal.org/project/storage)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Lightweight, bundle-able entities for storing structured data.

- **[FlexField](https://www.drupal.org/project/flexfield)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Defines a new "FlexField" field type that lets you create simple inline multiple-value fields without having to use entity references.

- **[Custom Field](https://www.drupal.org/project/custom_field)**  
  (Applies to: [Intangible](https://schema.org/Intangible))  
  Create simple, but flexible, multi-value fields without the hassle of entity references.

### Entity

_The below modules provide additional features to entity types._

- **[Metatag](https://www.drupal.org/project/metatag)**  
  Manages meta tags for all entities.

### Field Types/Field Enhancements

_The below modules provide more specific field types, enhancements, and behaviors._

Text

- **[Allowed Formats](https://www.drupal.org/project/allowed_formats)**  
  Limit which text formats are available for each field instance.

- **[Existing Values Autocomplete Widget](https://www.drupal.org/project/existing_values_autocomplete_widget)**  
  Provides an autocomplete widget for text fields that suggests all existing (previously entered) values for that field.

- **[Duration Field](https://www.drupal.org/project/duration_field)**  
  (Applies to: [Duration](https://schema.org/Duration))  
  Provides a duration field, that collects a period of time, whether that be hours, minutes, seconds, days, months, years, or any combination of those.

- **[Time Field](https://www.drupal.org/project/time_field)**  
  (Applies to: [Time](https://schema.org/Time))  
  Provides "Time Field" and "Time Range Field".

- **[Token Filter](https://www.drupal.org/project/token_filter)**  
  Makes token values available as an input filter.

- **[SmartDate](https://www.drupal.org/project/smart_date)**  
  (Applies to: [Date](https://schema.org/Date), [DateTime](https://schema.org/DateTime), [Schedule](https://schema.org/Schedule))  
  Provides the ability to store start and end times, with duration. Also provides an intelligent admin UI, and a variety of formatting options.

Numeric

- **[Range](https://www.drupal.org/project/range)**  
  (Applies to: [MonetaryAmount](https://schema.org/MonetaryAmount))  
  Defines a numeric range field type.

Link

**[Link Attributes](https://www.drupal.org/project/link_attributes)**  
  Provides a widget to allow settings of link attributes for menu links.

Image

- **[Focal Point](https://www.drupal.org/project/focal_point)**  
  Allows users to specify the focal point of an image for use during cropping.

References

- **[Corresponding Entity References](https://www.drupal.org/project/cer)**        
  Allows users to create two-way references between entities.

- **[Entity Reference Tree Widget](https://www.drupal.org/project/entity_reference_tree)**  
  Provides an entity relationship hierarchy tree widget for an entity reference field.

- **[Inline Entity Form](https://www.drupal.org/project/inline_entity_form)**  
  Provides a widget for inline management (creation, modification, removal) of referenced entities.\

Geolocation

- **[Geolocation Field](https://www.drupal.org/project/geolocation)**    
  Provides a field type to store geographical locations as pairs of latitude
  and longitude (lan,lng)

Demographic

- **[Address](https://www.drupal.org/project/address)**  
  (Applies to: [PostalAddress](https://schema.org/PostalAddress), [address](https://schema.org/address))  
  Provides functionality for storing, validating and displaying international postal addresses.

- **[Gender](https://www.drupal.org/project/gender)**  
  (Applies to: [GenderType](https://schema.org/GenderType), [gender](https://schema.org/gender))  
  Provides inclusive options for collecting gender information of individuals.

Business

- **[Office hours](https://www.drupal.org/project/office_hours)**  
  (Applies to: [OpeningHoursSpecification](https://schema.org/OpeningHoursSpecification))  
  Defines a 'weekly office hours' field type, allowing you to specify when an Entity is open or closed.

- **[Telephone Formatter](https://www.drupal.org/project/telephone_formatter)**  
  (Applies to: [telephone](https://schema.org/telephone))  
  Provides extra formatter to core's Telephone field

Options

- **[Select (or other)](https://www.drupal.org/project/select_or_other)**  
  (Applies to: [Enumeration](https://schema.org/Enumeration))  
  Provides a select box form element with additional option 'Other' to give a textfield.

- **[Select Text Value](https://www.drupal.org/project/select_text_value)**  
  (Applies to: [Enumeration](https://schema.org/Enumeration))  
  Widget for text fields to offer pre-define values for selection.

- **[Existing Values Autocomplete Widget](https://www.drupal.org/project/existing_values_autocomplete_widget)**  
  (Applies to: [Text](https://schema.org/Text))  
  Provides a widget for text fields that supplies an autocomplete callback with any previously entered (existing) values for the field.

Composite

- **[Double Field](https://www.drupal.org/project/double_field)**  
  (Applies to: [Intangible](https://schema.org/Intangible), [identifier](https://schema.org/identifier))  
  Provides a field type with two separate sub-fields.

- **[Key value field](https://www.drupal.org/project/key_value_field)**  
  (Applies to: [Intangible](https://schema.org/Intangible), [PropertyValue](https://schema.org/PropertyValue), [identifier](https://schema.org/identifier))  
  Provides a field with a key, value pair along with a description.

Calculation

- **[Computed Field](https://www.drupal.org/project/computed_field)**  
  Defines a field type that allows values to be "computed" via PHP code.

- **[Field Token Value](https://www.drupal.org/project/field_token_value)**  
  Provides a field allowing the value to be set using a string containing tokens.

Validation

- **[Field Validation](https://www.drupal.org/project/field_validation)**    
  Provides customizable field validation rules.

### Integrations

- **[Next.js](https://www.drupal.org/project/next)**  
  Next.js + Drupal for Incremental Static Regeneration and Preview mode.
