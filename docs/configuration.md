---
title: Configuration
description: Configure Panelist's sidebar width and keyboard shortcuts globally or per panel.
---

# Configuration

Panelist has two settings. Set them globally in the config file, or per panel on the plugin.

## Config file

Publish the config file as described in [Installation](installation.md). It contains:

```php
// config/panelist.php
return [
    'sidebar_width' => 320,
    'enable_keyboard_shortcuts' => true,
];
```

| Option | Default | Description |
|---|---|---|
| `sidebar_width` | `320` | The sidebar's initial width in pixels, used until the user resizes it. |
| `enable_keyboard_shortcuts` | `true` | Whether the [keyboard shortcuts](keyboard-shortcuts.md) are available. |

## Per-panel settings

To override the config for a single panel, use the plugin's fluent methods:

```php
PanelistPlugin::make()
    ->sidebarWidth(400)
    ->keyboardShortcuts(false)
```

A value set on the plugin takes precedence over the config file.
