# Local Development
The editoria11y development team uses `ddev` and the drupalspoons composer
plugin to do local development.

## Helpful tools and tips
1. Remember to run `./scripts/get.sh` from the project root to grab the latest
copy of the library from GitHub.
2. Use an API development tool, like Postman or RESTman, to test things like the
dismiss API.

## DDEV
If you choose to use DDEV for local development, please follow these
instructions.

### Setup
1. Make sure docker is up to date.
2. [Install .direnv](https://direnv.net/docs/installation.html)
3. [Install DDEV](https://ddev.readthedocs.io/en/stable/#macos-homebrew)
4. Create a clean directory
5. Clone the contrib project from d.o. into the new directory.
6. Enter the new directory, then install the composer plugin with the [Drupal Spoons](https://gitlab.com/drupalspoons/composer-plugin) default configuration:  `bash <(curl -s https://gitlab.com/drupalspoons/composer-plugin/-/raw/master/bin/setup)`)
7. Create a .ddev folder in the module root, create a config.yaml file, and
paste in the text at bottom.
8. Run `ddev start`. Containers should now be pulled.
9. If everything is successful, run `ddev describe` to see a list of URLs.
10. Go to ` https://editoria11y.ddev.site/` to install drupal.

### Use
1. Run `ddev start` from the project root.
2. Test site at `https://editoria11y.ddev.site/`.
3. Prefix drush commands with ddev (`ddev drush cr`).
4. Run `ddev composer drupalspoons:rebuild` as needed to rebuild the module's
filesystem.

Tip: DDEV supports PHP zero-conf debugging with PHPStorm. You just need to tell
PHPStorm to listen for debug with  and by running `ddev xdebug on` from the bash
prompt.

 ## Config.yaml contents (see step 7)
```
name: editoria11y
type: drupal10
docroot: web
php_version: "8.1"
webserver_type: apache-fpm
router_http_port: "80"
router_https_port: "443"
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
database_type: mariadb
version: "10.3"
nfs_mount_enabled: false
mutagen_enabled: true
use_dns_when_possible: true
composer_version: ""
web_environment:
- COMPOSER=composer.spoons.json
- SIMPLETEST_DB=mysql://db:db@db/db
- SIMPLETEST_BASE_URL=http://127.0.0.1
  nodejs_version: "16"
```
