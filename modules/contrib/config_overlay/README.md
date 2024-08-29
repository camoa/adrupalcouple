# Config Overlay

This module will turn the synchronization storage into an overlay of the shipped
configuration of all enabled extensions.

When exporting configuration this module will remove any configuration that is
identical to configuration provided by an installed extension from the export.
Thus, only configuration that has been added or modified will be written to the
synchronization directory. When importing configuration the extensions'
configuration is amended to the configuration in the synchronization directory,
so that the missing files are transparent the import works as expected.

Because shipped configuration (in contrast to the active configuration) does not
contain a UUID or a default configuration hash, the `uuid` and `_core` keys are
ignored when comparing active and shipped configuration. When reading the
extension configuration as part of the overlay, the respective UUIDs and hashes
of the active configuration are amended automatically so that the configuration
import does not detect any differences relative to the active configuration.

Note that due to this the configuration system will not be able to detect
configuration that has been deleted and recreated with the same name when this
module is installed. Such configuration will be detected as an update. If
configuration is recreated to be exactly the same as before (but for the UUID)
this will not be detected as a change with this module.

Deleting a shipped configuration entity is supported by tracking the list of
deleted shipped configuration in the `config_overlay.deleted` configuration
object, which will appear in the configuration export once any shipped
configuration is deleted.

## Config Split Integration

Config Overlay will overlay any configuration splits before Config Split
transforms the configuration so that if you use both Config Overlay and Config
Split you can ship configuration splits in modules and have everything work as
expected. To avoid the split directories containing copies of unchanged shipped
configuration you can make the splits _stack-able_.

### Nested Splits

If you have configuration splits that are shipped by modules that are themselves
split off by another configuration split, Config Split will not detect those
"nested" splits during the initial configuration import. To fix this, you need
to either perform another configuration import or create the respective splits
manually prior to the configuration import, for example using a post-update
hook.

### Split Priorities

Config Overlay runs with priority 100 (and -100) both on import and export.
Because of this, if you are using the `$settings['config_split_priorities']`
variable in `settings.php` to set the priority of a split to above 100, it will
always contained all shipped configuration even if you make it stack-able. The
split will also not be detected by Config Overlay in the initial configuration
import if it is shipped in a module.
