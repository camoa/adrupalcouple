# Dropdown Language Module

## Contents of this file

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Troubleshooting
  * Maintainers


## Introduction

  The Dropdown Language module provides a block with D8's Dropbutton element to
  switch site language. It adds an additional block. The Language Switcher Block
  (Language modules basic block) is an unordered list of links.

  * For a full description of the module visit: [Project Page](https://www.drupal.org/project/dropdown_language)

  * To submit bug reports and feature suggestions, or to track changes visit: [Issue Queue](https://www.drupal.org/project/issues/dropdown_language)


## Requirements

  This module requires no modules outside of Drupal core.


## Installation

  * Install the Dropdown Language module as you would normally install a
    contributed Drupal module. Visit [Installing modules](https://www.drupal.org/docs/extending-drupal/installing-modules) for
    further information.


## Configuration

  1. Navigate to Administration > Extend and enable the module.
  2. Navigate to Administration > Configuration > Regional and Language >
     Languages and add desired languages. Save configuration.
  3. Navigate to Administration > Configuration > Regional and Language >
     Dropdown Language Switcher (/admin/config/regional/dropdown-language-switcher).
  4. Select how to display the language labelling from the dropdown: Show
     Language Name, Show Language ID, or Use Custom Labels for Language Names
    (per block instance).
  5. There is also the option to "SWITCH LANGUAGE" Decor which provides the
     block with fieldset wrapping.
  6. Navigate to Administration > Structure > Block layout and place the
     "Dropdown Language" block.


## Troubleshooting

 * Placed Blocks will be visible when two, or more languages are active.
   Navigate to Administration > Configuration > Regional and Language > Languages
   (/admin/config/regional/language).

 * To be able to translate strings (ie: "Switch Language" label)
   Navigate to Administration -> Configuration -> Regional and Language -> Languages,
   then click 'Edit' in the English row, check the 'Enable interface
   translation to English' box, and click save. (admin/config/regional/language/edit/en)


## Maintainers

  * skaught (SKAUGHT) - [https://www.drupal.org/u/skaught](https://www.drupal.org/u/skaught)
