Table of contents
-----------------

* Introduction
* Features
* Notes
* Usage
* Todo


Introduction
------------

The **Schema.org Blueprints Starter Kit (API)** module provides an API for 
starterkits to create Schema.org types.


Features
--------

- Allows a starterkit/module to change Schema.org configuration before any
  Schema.org types are created.
- Allows a starterkit/module to declare what Schema.org types are required
  preinstallation.
- Post module installation, re-imports optional configuration and rewrites 
  configuration via the config_rewrite.module. This allows a starterkit
  via `hook_install()` to create additional configuration settings.
- Provide adminstrative page to install starter kits and generate/kill content. 


Notes
-----

## Startkit setup

- Any exported configuration that relies on generated Schema.org configuration
  should be stored in /config/optional.
- When possible use export optional (`/config/optional`) 
  or rewrite (`/config/rewrite`) configuration.
- Use `hook_install()` to generate content entities.
- Use `hook_install()` to do advanced configuration customization.

## Starter kit module phases

### Pre-install

- Rewrites any schemadotorg* configuration in `/config/rewrite`.   
  _This allows starter kits to adjust the 
   Schema.org Blueprints module configuration._
- Creates Schema.org types via *.schemadotorg_starterkit.yml
- Rewrites existing and newly created configuration.
- Imports starterkit's optional configuration.  

### Install

- Allows start kits to use `hook_install()` to generate content and make
  programmatic tweaks.

  
Usage
-----

Create a `MODULE_NAME.schemadotorg_starterkit.yml` file

Inside the `MODULE_NAME.schemadotorg_starterkit.yml` file declare what 
Schema.org types and properties should be created preinstallation.

```
# Declare a dependency on a another starter while still allowing schemadotorg*
# config files to rewritten.
dependencies:
  - some_other_startkit
# Declare which Schema.org types should be created.
types:
  'node:Event':
    properties:
      eventSchedule:
        label: When
        description: 'Enter when is the event occuring.'
      image: false
      eventStatus: false
      location: false
      organizer: false
      performer: false
      # Declare a custom field to be created.
      custom:
        name: custom
        type: string
        label: Custom
        group: general
```


Tips
----

`MODULE_NAME.schemadotorg_starterkit.yml` file

- Explicitly include any Schema.org properties that are required.
- Generally, you want to add include additional Schema.org properties 
  and not excluded any  Schema.org properties. 


Todo
----

- Improve custom dependency management code which is a little brittle when
  there are missing dependencies.
