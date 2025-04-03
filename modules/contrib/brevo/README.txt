* Introduction

Brevo module provides integration with Brevo's Official SDK
for PHP - https://github.com/getbrevo/brevo-php.

Transactional emailing is supported through the submodule "Brevo Mailer".
The contributed Drupal module Symfony Mailer is also supported.

* Requirements

This module requires the following modules and libraries:

 - Brevo SDK (https://github.com/getbrevo/brevo-php)
 - Html2Text (https://github.com/mtibben/html2text)

If you want to send transactional emails through Brevo,
then one of the following modules, according to your preferences:
 - Mail System (https://www.drupal.org/project/mailsystem)
 - Symfony Mailer (https://www.drupal.org/project/symfony_mailer)

If you want to use Symfony Mailer, make sure to also install the following dependencies:
 - Brevo integration for Symfony Mailer (https://github.com/symfony/brevo-mailer)
 - Symfony HTTP Client (https://github.com/symfony/http-client)

* Recommended modules

 - Reroute Email (https://www.drupal.org/project/reroute_email)

* Installation

Install this module as usual by Composer: composer require drupal/brevo

* Configuration

1. Go to https://www.brevo.com and sign up for a Brevo account.
2. Configure API settings on the Brevo settings page:
  admin/config/services/brevo/settings.
3. If using MailSystem, enable Brevo as Default (or Module-specific) Mail System on the
  Mail System admin page (admin/config/system/mailsystem).
   If using Symfony Mailer, enable Brevo transport as the default on the
  Symfony Mailer transports page (admin/config/system/mailer/transport).

* Maintainers

 - @renrhaf (https://www.drupal.org/u/renrhaf)
 - @chris-matthews (https://www.drupal.org/u/chris-matthews)

* Supporting organizations

 - Brevo (https://www.drupal.org/brevo)
 - SaaS Production (https://www.drupal.org/saas-production)
