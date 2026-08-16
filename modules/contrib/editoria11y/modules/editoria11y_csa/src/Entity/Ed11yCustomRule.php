<?php

declare(strict_types=1);

namespace Drupal\editoria11y_csa\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\editoria11y_csa\Ed11yCustomRuleInterface;

/**
 * Defines the editoria11y custom test entity type.
 *
 * @ConfigEntityType(
 *   id = "ed11y_custom_test",
 *   label = @Translation("Editoria11y Custom Test"),
 *   label_collection = @Translation("Editoria11y Custom Tests"),
 *   label_singular = @Translation("editoria11y custom test"),
 *   label_plural = @Translation("editoria11y custom tests"),
 *   config_prefix = "ed11y_custom_test",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\editoria11y_csa\Ed11yCustomRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\editoria11y_csa\Form\Ed11yCustomRuleForm",
 *       "edit" = "Drupal\editoria11y_csa\Form\Ed11yCustomRuleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/editoria11y/custom-tests",
 *     "add-form" = "/admin/config/content/editoria11y/custom-tests/add",
 *     "edit-form" = "/admin/config/content/editoria11y/custom-tests/{ed11y_custom_test}",
 *     "delete-form" = "/admin/config/content/editoria11y/custom-tests/{ed11y_custom_test}/delete",
 *   },
 *   admin_permission = "administer editoria11y checker",
 *   label_count = {
 *     "singular" = "@count editoria11y custom test",
 *     "plural" = "@count editoria11y custom tests",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "test_key",
 *     "test_name",
 *     "tip_content",
 *     "element_set",
 *     "type",
 *     "dismiss_key",
 *     "filter_selector",
 *     "include_text",
 *     "exclude_text",
 *     "case_sensitive",
 *   },
 * )
 */
final class Ed11yCustomRule extends ConfigEntityBase implements Ed11yCustomRuleInterface {

  /**
   * The config entity ID.
   */
  protected string $id;

  /**
   * The admin label.
   */
  protected string $label;

  /**
   * The test key used to group results in the JS library.
   */
  protected string $test_key = '';

  /**
   * The tooltip title shown in the accessibility tip.
   */
  protected string $test_name = '';

  /**
   * The HTML content displayed in the tooltip.
   */
  protected string $tip_content = '';

  /**
   * The element set to check (e.g., Links, Headings).
   */
  protected string $element_set = '';

  /**
   * The alert type: 'error' or 'warning'.
   */
  protected string $type = 'error';

  /**
   * The dismiss key type: 'text', or 'attributes'.
   */
  protected string $dismiss_key = 'text';

  /**
   * Optional CSS selector to filter elements.
   */
  protected string $filter_selector = '';

  /**
   * Text strings to include (flag if element contains one).
   */
  protected array $include_text = [];

  /**
   * Text strings to exclude (don't flag if element contains one).
   */
  protected array $exclude_text = [];

  /**
   * Whether text matching is case sensitive.
   */
  protected bool $case_sensitive = FALSE;

}
