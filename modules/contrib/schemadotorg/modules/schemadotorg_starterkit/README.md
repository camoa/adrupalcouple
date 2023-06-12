Table of contents
-----------------

* Introduction
* Features
* Notes
* Usage


Introduction
------------

The **Schema.org Blueprints Starterkit (API)** module provides an API for 
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
- Provide adminstrative page to install starterkits and generate/kill content. 


Notes
-----

## Startkit setup

- Any exported configuration that relies on generated Schema.org configuration
  should be stored in /config/optional.
- When possible use export optional (`/config/optional`) 
  or rewrite (`/config/rewrite`) configuration.
- Use `hook_install()` to generate content entities.
- Use `hook_install()` to do advanced configuration customization.

## Starterkit module phases

### Pre-install

- Rewrites any schemadotorg* configuration in `/config/rewrite`.   
  _This allows starterkits to adjust the 
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
```


Tips
----

`MODULE_NAME.schemadotorg_starterkit.yml` file

- Explicitly include any Schema.org properties that are required.
