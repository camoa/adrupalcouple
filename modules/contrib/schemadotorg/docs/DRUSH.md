Schema.org Blueprints: Drush commands
-------------------------------------

# Available commands

```
drush list --filter=schemadotorg

Available commands:
schemadotorg:
  schemadotorg:create-type (socr)   Create Schema.org types.
  schemadotorg:delete-type (sode)   Delete Schema.org type.
  schemadotorg:repair (sorp)        Update Schema.org repair.
  schemadotorg:set-generate (sosg)  Generate the Schema.org mapping set.
  schemadotorg:set-kill (sosk)      Kill the Schema.org mapping set.
  schemadotorg:set-setup (soss)     Setup the Schema.org mapping set.
  schemadotorg:set-teardown (sost)  Teardown the Schema.org mapping set.
  schemadotorg:update-schema (soup) Update Schema.org data.
```

# Usage

## List Schema.org Blueprints modules

```
# List core modules.
drush pm:list --package='Schema.org Blueprints';

# Install all Schema.org Blueprints modules.
drush pm-list --format=list | grep schemadotorg | xargs drush install -y

# Install Schema.org Blueprints Core modules.
drush pm-list --format=list --package='Schema.org Blueprints Core' | xargs drush install -y

# List JSON:API and JSON-LD modules.
drush pm:list --package='Schema.org Blueprints API';

# List integration modules.
drush pm:list --package='Schema.org Blueprints Extras';

# List demo modules.
drush pm:list --package='Schema.org Blueprints Demo';
```

## Install Schema.org Blueprints modules

```
# Install all Schema.org Blueprints modules.
drush pm-list --format=list | grep schemadotorg | xargs drush install -y

# Install Schema.org Blueprints Core modules.
drush pm-list --format=list --package='Schema.org Blueprints Core' | xargs drush install -y
```

## Setup and generate individual Schema.org types with example content.

```
# Generate Schema.org types.
drush schemadotorg:create-type -y paragraph:ContactPoint paragraph:PostalAddress
drush schemadotorg:create-type -y media:AudioObject media:DataDownload media:ImageObject media:VideoObject
drush schemadotorg:create-type -y user:Person
drush schemadotorg:create-type -y node:Person node:Organization node:Place node:Event

# Generate content (skip menu_link fields which throw fatal errors).
drush devel-generate:users --kill 50
drush devel-generate:media --kill 50
drush devel-generate:content --kill --skip-fields=menu_link\
 --add-type-label\
 --bundles=person,organization,place,event 50
```

## Kill and teardown individual Schema.org types with example content.

```
# Delete content.
drush devel-generate:users --kill 0
drush devel-generate:media --kill 0
drush devel-generate:content --kill 0

# Delete Schema.org types.
drush schemadotorg:delete-type -y --delete-fields user:Person
drush schemadotorg:delete-type -y --delete-fields media:AudioObject media:DataDownload media:ImageObject media:VideoObject
drush schemadotorg:delete-type -y --delete-entity paragraph:ContactPoint paragraph:PostalAddress
drush schemadotorg:delete-type -y --delete-entity node:Person node:Organization node:Place node:Event
```

## Setup, generate, ki``ll and teardown a set Schema.org types.

```
# Setup Schema.org mapping set.
drush schemadotorg:set-setup common

# Generate Schema.org mapping set content.
drush schemadotorg:set-generate common

# Kill (delete) Schema.org mapping set content.
drush schemadotorg:set-kill common

# Teardown (delete) Schema.org mapping set content.
drush schemadotorg:set-teardown common
```

# Download, install, and update the Schema.org CSV files to the latest version.

@see <https://schema.org/docs/releases.html>
@see /admin/reports/schemadotorg/docs/names

```
drush schemadotorg:download-schema
drush schemadotorg:install-schema
drush schemadotorg:translate-schema
```
