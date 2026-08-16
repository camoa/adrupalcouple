<?php

/**
 * @file
 * Markdown Easy module hook definitions.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the Markdown converter configuration.
 *
 * @param array $config
 *   The Markdown converter's configuration to be modified.
 *
 * @ingroup markdown_easy
 */
function hook_markdown_easy_config_modify(array &$config) {
}

/**
 * Modify the Markdown environment.
 *
 * @param \League\CommonMark\Environment\Environment $environment
 *   The Markdown environment that contains the current parsers and configs.
 *
 * @ingroup markdown_easy
 */
function hook_markdown_easy_environment_modify(Environment &$environment) {
}

/**
 * @} End of "addtogroup hooks".
 */
