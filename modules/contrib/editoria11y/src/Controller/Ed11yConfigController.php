<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\State\StateInterface;
use Drupal\editoria11y\CSAStatus;
use Drupal\editoria11y\TestNames;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns global Editoria11y configuration as cacheable JSON.
 *
 * The response is browser-cached via URL versioning: the JS client appends
 * a ?v= parameter that changes when configuration is updated.
 */
final class Ed11yConfigController extends ControllerBase {

  public function __construct(
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('state'),
    );
  }

  /**
   * Returns the global configuration JSON.
   */
  public function getConfig(): CacheableJsonResponse {
    $config = $this->config('editoria11y.settings');

    $cacheMetadata = new CacheableMetadata();

    $cacheMetadata->addCacheableDependency($config);
    $cacheMetadata->addCacheTags(['editoria11y:dismissals', 'editoria11y:config']);
    $cacheMetadata->addCacheContexts(['user.permissions']);
    $cacheMetadata->setCacheMaxAge(2628000);

    $data = [];
    $passthrough_keys = [
      'preserve_params', 'include_null_params', 'assertiveness', 'no_load',
      'hide_edit_links', 'ignore_all_if_absent', 'content_root',
      'shadow_components', 'detect_shadow', 'ignore_elements',
      'panel_no_cover', 'panel_pin', 'embedded_content_warning',
      'extra_placeholder_alts', 'hidden_handlers', 'live_h_inherit',
      'live_h2', 'live_h3', 'live_h4', 'disable_live', 'download_links',
      'link_strings_new_windows', 'link_ignore_selector',
      'watch_for_changes', 'custom_tests', 'element_hides_overflow',
      'ed11y_theme', 'ignore_link_strings',
    ];
    foreach ($passthrough_keys as $key) {
      $data[$key] = $config->get($key);
    }
    $module_handler = $this->moduleHandler();

    // Build the list of tests the library should leave disabled in the
    // browser. This is the union of:
    // - User-toggled tests (config:tests_off).
    // - Library artifacts: upstream defaults that the Drupal module does not
    //   wire up (LINK_LABEL, the LANG/PAGE_LANG suite). These would otherwise
    //   surface as active in Drupal.Ed11y.State.option.checks even though no
    //   rule ever fires.
    // - When CSA is inactive: every template (non-content) test. CSA is the
    //   only mechanism the module exposes for enabling developer-tier checks,
    //   so without it those tests must not run, regardless of upstream
    //   library defaults.
    $ignore_tests = empty($config->get('tests_off')) ? [] :
      explode(',', $config->get('tests_off'));
    $ignore_tests = array_merge($ignore_tests, TestNames::libraryArtifacts());
    $csa_active = $module_handler->moduleExists('editoria11y_csa') &&
      CSAStatus::current($this->state)->isActive();
    if (!$csa_active) {
      $ignore_tests = array_merge($ignore_tests, TestNames::templateTests());
    }
    $data['ignore_tests'] = array_values(array_unique($ignore_tests));

    // @todo clear cache if one of these modules is enabled.
    $data['ext_link_modules'] = $module_handler->moduleExists('link_purpose') ||
      $module_handler->moduleExists('extlink');

    // Allow submodules to add their global config and cache metadata.
    $module_handler->invokeAll('editoria11y_alter_global_config', [
      &$data,
      $cacheMetadata,
    ]);

    $response = new CacheableJsonResponse($data);
    $response->addCacheableDependency($cacheMetadata);
    return $response;
  }

}
