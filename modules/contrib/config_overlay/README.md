# Config Overlay

This module will turn the synchronization storage into an overlay of the shipped
configuration of all enabled extensions.

When exporting configuration this module will remove any configuration that is
identical to configuration provided by an installed extension from the export.
Thus, only configuration that has been added or modified will be written to the
synchronization directory. When importing configuration the extensions'
configuration is amended to the configuration in the synchronization directory,
so that the import works as expected.

Deleting shipped configuration is supported by tracking the list of deleted
shipped configuration in the `config_overlay.deleted` configuration object,
which will appear in the configuration export once any shipped configuration is
deleted. Config Overlay will not overlay configuration that is listed in that
file.

## UUIDs and Default Configuration Hashes

Because shipped configuration (in contrast to the active configuration) does not
contain a UUID or a default configuration hash, the `uuid` and `_core` keys are
ignored when comparing active and shipped configuration. When reading the
shipped configuration during configuration import, the respective UUIDs and
hashes of the active configuration are amended automatically so that the
configuration import does not detect any differences relative to the active
configuration because of this.

Note that there are edge cases, where due to this Config Overlay technically
causes a behavioral change:

* If a shipped configuration item is deleted and subsequently recreated with the
  same name on a site with an existing configuration export, Drupal normally
  detects the change in UUIDs in the export and deletes and recreates the
  configuration item when the updated configuration is imported. With Config
  Overlay this will be detected as an update instead (or not at all if the
  recreated configuration exactly matches the shipped state).
* If a module that ships configuration updates that configuration and is
  subsequently uninstalled and re-installed (and, thus, the shipped
  configuration is re-installed) on a site with an existing configuration export
  and then the configuration is changed back to the previous state, Drupal
  normally detects this as an update because the default configuration hash has
  changed. With Config Overlay this will not be detected as a change.

Even if you do one of these cases, it is generally inconsequential, but if you
_do_ in fact rely on the specific behavior in Drupal core for some reason, you
should not use Config Overlay.

## Integration

### Config Ignore

Config Overlay works together with Config Ignore. Config Overlay does not track
deletions for shipped configuration that is ignored, so that deleted-but-ignored
shipped configuration is not accidentally "restored".

Note that only Config Ignore 3.x is supported.

### Config Split

Config Overlay will overlay any configuration splits before Config Split
transforms the configuration so that if you use both Config Overlay and Config
Split you can ship configuration splits in modules and have everything work as
expected. To avoid the split directories containing copies of unchanged shipped
configuration you can make the splits _stack-able_.

Note that using Config Split 1.x with Config Overlay is not supported because
Config Split 1.x is not compatible with Config Filter 2.x and with Config Filter
1.x there is no way for Config Overlay to process the configuration after
Config Split.

## Config Transformation Priorities

Config Overlay runs late when exporting configuration (with priority -50) to
remove the shipped configuration and early when importing configuration (with
priority 50) to re-add it.

Config Ignore runs after Config Overlay (with priority -100 on both export and
import). (Note that only Config Ignore 3.x is supported.)

If Config Split is installed, Config Overlay additionally runs early when
exporting configuration (with priority 50) to remove shipped configuration so it
does not get split off for stack-able configuration splits and late when
importing configuration (with priority -50) to re-add that shipped configuration
for stack-able configuration splits.

You can override the priorities used by Config Overlay by specifying a list of
integers as the value for `$settings['config_overlay_priorities']` in
`settings.php`. For example:

```php
// This is the default value (without Config Split).
$settings['config_overlay_priorities'] = [-50];

// This is the default value if Config Split (2.x) is installed.
$settings['config_overlay_priorities'] = [50, -50];

// Add a third transformation
$settings['config_overlay_priorities'] = [50, -50, -60];
```

Note that priorities should be greater than -100.

Custom Config Overlay priorities can be helpful for the following use-cases:

### Custom Split Priorities

If you have are using Config Split (2.x) with the
`$settings['config_split_priorities']` variable in `settings.php` to set the
priority of a split to above 50 or below -50, it will always contain all shipped
configuration even if you make it stack-able. The split will also not be
detected by Config Overlay in the initial configuration import if it is shipped
in a module. To rectify this, set the `$settings['config_overlay_priorities']`
variable such that Config Overlay always runs before and after every
configuration split.

For example:

```php
$settings['config_split_priorities'] = [
  'my_early_split' => 60,
  'my_late_split' => -60,
];
$settings['config_overlay_priorities'] = [70, -70];
```

### Nested Shipped Splits

If you have configuration splits that are shipped by modules that are themselves
split off by another configuration split, Config Split will not detect those
"nested" splits during the initial configuration import. To rectify this, set
the `$settings['config_split_priorities']` and
`$settings['config_overlay_priorities']` variables such that Config Overlay
always runs in between each level of nesting.

For example:

```php
$settings['config_split_priorities'] = [
  'shipped_in_module_aaa_splits_module_bbb' => 0,
  'shipped_in_module_bbb_splits_module_ccc' => -20,
  'shipped_in_module_ccc_splits_module_ddd' => -40,
];
$settings['config_overlay_priorities'] = [50, -10, -30, -50];
```
