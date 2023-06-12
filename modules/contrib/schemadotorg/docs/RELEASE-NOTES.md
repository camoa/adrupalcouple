Steps for creating a new release
--------------------------------

  1. Review code
  2. Deprecated code
  3. Generate release notes
  4. Tag and create a new release
  5. Tag and create a hotfix release


1. Review code
--------------

    # Remove files that should never be reviewed.
    cd modules/sandbox/schemadotorg
    rm *.patch interdiff-*

[PHP Linting](https://www.drupal.org/node/1587138)

    # Install drupal/coder (One time).
    composer global require drupal/coder

    # Update drupal/coder.
    composer global update drupal/coder

    # List coding standards.
    phpcs -i

    # Check Drupal PHP coding standards and best practices.
    phpcs .

    # Show sniff codes in all reports.
    phpcs -s .

    # Fix coding standards.
    phpcbf .

[PHP version compatibility](https://www.drupal.org/node/2924272)

    # Install PHP version compatibility (One-time)
    cd ~/Sites/drupal_next
    composer require --dev phpcompatibility/php-compatibility

    # Check PHP version compatibility.
    cd ~/Sites/drupal_next/web
    ../vendor/bin/phpcs --runtime-set testVersion 8.0 --standard=../vendor/phpcompatibility/php-compatibility/PHPCompatibility --extensions=php,module,inc,install,test,profile,theme modules/sandbox/schemadotorg > ~/schemadotorg-php-compatibility.txt
    cat ~/schemadotorg-php-compatibility.txt

[JavaScript](https://www.drupal.org/node/2873849)

@see https://stackoverflow.com/questions/58424718/how-can-i-disable-the-error-prettier-prettier-on-eslint

    # Install Eslint. (One-time)
    cd ~/Sites/drupal_next/web/core
    yarn install

    # Check Drupal JavaScript (ES6) coding standards.
    cd ~/Sites/drupal_next/web
    core/node_modules/.bin/eslint --no-eslintrc -c=core/.eslintrc.json --ext=.js modules/sandbox/schemadotorg > ~/schemadotorg-javascript-coding-standards.txt
    cat ~/schemadotorg-javascript-coding-standards.txt

[CSS](https://www.drupal.org/node/3041002)

    # Install Eslint. (One-time)
    cd ~/Sites/drupal_next/web/core
    yarn install

    cd ~/Sites/drupal_next/web/core
    yarn run lint:css ../modules/sandbox/schemadotorg/css --fix

[Spell Check](https://www.drupal.org/node/3122084) for Drupal 9.1+

    # Install Pspell. (One-time)
    cd ~/Sites/drupal_next/web/core
    yarn install

    # Update dictionary. (core/misc/cspell/dictionary.txt)

    cd ~/Sites/drupal_next/web/
    cat modules/sandbox/schemadotorg/cspell/dictionary.txt >> core/misc/cspell/dictionary.txt

    cd ~/Sites/drupal_next/web/core
    yarn run spellcheck ../modules/sandbox/schemadotorg/**/* > ~/schemadotorg-spell-check.txt
    cat ~/schemadotorg-spell-check.txt


[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644


2. Deprecated code
------------------

[drupal-check](https://mglaman.dev/blog/tighten-your-drupal-code-using-phpstan) - RECOMMENDED

Install PHPStan

    cd ~/Sites/drupal_next
    composer require \
      phpstan/phpstan \
      phpstan/extension-installer \
      phpstan/phpstan-deprecation-rules \
      mglaman/phpstan-drupal

Run PHPStan with level 2 to catch all deprecations.
@see <https://phpstan.org/user-guide/rule-levels>

    cd ~/Sites/drupal_next
    ./vendor/bin/phpstan --level=2 analyse web/modules/sandbox/schemadotorg > ~/schemadotorg-deprecated.txt
    cat ~/schemadotorg-deprecated.txt

[Drupal Rector](https://github.com/palantirnet/drupal-rector)

Install Drupal Rector

    cd ~/Sites/drupal_next
    composer require palantirnet/drupal-rector --dev
    cp vendor/palantirnet/drupal-rector/rector.php .

Run Drupal Rector

    cd ~/Sites/drupal_next
    ./vendor/bin/rector process web/modules/sandbox/schemadotorg --dry-run    
    ./vendor/bin/rector process web/modules/sandbox/schemadotorg


3. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)

    drush release-notes --nouser 1.0.0-VERSION 1.0.x


4. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git checkout 1.0.x
    git up
    git tag 1.0.0-VERSION
    git push --tags
    git push origin tag 1.0.0-VERSION

[Create new release](https://www.drupal.org/node/add/project-release/2640714)


5. Tag and create a hotfix release
----------------------------------

    # Creete hotfix branch
    git checkout 1.0.LATEST-VERSION
    git checkout -b 1.0.NEXT-VERSION-hotfix
    git push -u origin 1.0.NEXT-VERSION-hotfix

    # Apply and commit remote patch
    curl https://www.drupal.org/files/issues/[project_name]-[issue-description]-[issue-number]-00.patch | git apply -
    git commit -am 'Issue #[issue-number]: [issue-description]'
    git push

    # Tag hotfix release.
    git tag 1.0.NEXT-VERSION
    git push --tags
    git push origin tag 1.0.NEXT-VERSION

    # Merge hotfix release with HEAD.
    git checkout 1.0.x
    git merge 1.0.NEXT-VERSION-hotfix

    # Delete hotfix release.
    git branch -D 1.0.NEXT-VERSION-hotfix
    git push origin :1.0.NEXT-VERSION-hotfix
