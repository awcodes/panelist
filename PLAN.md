# Filament Panelist Plugin — Implementation Plan

## Overview

Build a reusable Filament plugin that transforms standard resource CRUD interfaces into a **panelist-style editing experience** similar to Gmail, Notion, or Linear.

The plugin introduces a **master–detail layout** where:

* The **left panel** displays a searchable resource table.
* The **right panel** displays the form editor for the selected record.
* Record selection and editing occur **without page navigation**.

The plugin must be **opt-in**, flexible, and compatible with existing Filament resource configurations.

---

# Phase 1 — Plugin Foundation

## 1. Create the Package

Create a new Laravel package.

Directory structure:

```
panelist
├── src
│   ├── PanelistPlugin.php
│   ├── Pages
│   │   └── PanelistPage.php
│   ├── Livewire
│   │   ├── SidebarTable.php
│   │   └── RecordEditor.php
│   ├── Concerns
│   │   └── HasPanelist.php
│   ├── Support
│   │   └── ResourceResolver.php
│   └── PanelistServiceProvider.php
│
├── resources
│   └── views
│       └── pages
│           └── panelist.blade.php
│
├── config
│   └── panelist.php
│
└── composer.json
```

---

## 2. Register Plugin

Create the plugin class.

Responsibilities:

* Register views
* Load configuration
* Attach Livewire components
* Register optional resource overrides

Example responsibilities:

```
PanelistPlugin
 - boot views
 - register Livewire components
 - optionally auto-register resources
```

---

# Phase 2 — Configuration System

## Publish Config

Create:

```
config/panelist.php
```

Example:

```
return [

    'auto_register_resources' => false,

    'sidebar_width' => 320,

    'enable_keyboard_shortcuts' => true,

];
```

### Goals

* Keep plugin safe by default.
* Allow global panelist adoption.

---

# Phase 3 — Resource Integration

## Trait: HasPanelist

Allow resources to opt into the panelist UI.

Example usage:

```
class PostResource extends Resource
{
    use HasPanelist;
}
```

Trait responsibilities:

* Mark resource as panelist-enabled
* Provide extension hooks later

---

## Automatic Resource Detection

Plugin checks:

```
if resource uses HasPanelist
```

or

```
config('auto_register_resources')
```

Then replaces the resource index page with the panelist page.

---

# Phase 4 — Panelist Page

Create the page responsible only for layout.

Responsibilities:

* Render sidebar
* Render editor
* Maintain shared state
* Pass resource reference to components

Example layout concept:

```
+----------------------+-----------------------+
| SidebarTable         | RecordEditor          |
|                      |                       |
| Resource table       | Resource form         |
|                      |                       |
+----------------------+-----------------------+
```

---

# Phase 5 — Sidebar Table Component

## Responsibilities

The sidebar handles:

* resource table
* search
* filters
* selecting records
* create record action

## Implementation

Use Filament’s table system:

```
InteractsWithTable
```

Data source:

```
Resource::getEloquentQuery()
```

Table columns:

```
Resource::table()
```

## Record Selection

When clicking a record:

```
dispatch('panelist-record-selected')
```

Payload:

```
record id
```

---

# Phase 6 — Record Editor Component

## Responsibilities

The editor handles:

* loading record
* rendering resource form
* create mode
* save operations
* validation

## Record Loading

Listen for event:

```
panelist-record-selected
```

Then load record.

---

## Form Rendering

Reuse resource form definition:

```
Resource::form()
```

Bind state to Livewire.

---

## Create Mode

Triggered from sidebar:

```
panelist-create-record
```

Editor resets form and initializes a new model.

---

## Save Action

Steps:

1. Validate form
2. Persist record
3. Dispatch event:

```
panelist-record-saved
```

Sidebar listens and refreshes table.

---

# Phase 7 — URL State Sync

Enable record deep-linking.

Example URL:

```
/admin/posts?record=15
```

Implementation:

* store record ID in Livewire property
* sync with URL
* load record on mount

Benefits:

* sharable links
* refresh-safe state
* browser navigation compatibility

---

# Phase 8 — Resizable Panelist Layout

Replace fixed grid layout with adjustable panels.

Implementation:

Use Alpine.js.

```
sidebar width variable
drag handle
```

Persist width using:

```
localStorage
```

Config fallback:

```
sidebar_width
```

---

# Phase 9 — Keyboard Navigation

Add optional keyboard shortcuts.

Examples:

| Shortcut | Action               |
|----------|----------------------|
| ↑ ↓      | move between records |
| enter    | open selected record |
| cmd + s  | save                 |
| cmd + n  | create               |
| esc      | cancel editing       |

Implementation:

* listen for keydown events
* dispatch Livewire events

Feature toggle:

```
enable_keyboard_shortcuts
```

---

# Phase 10 — Table Refresh Behavior

After saving:

```
panelist-record-saved
```

Sidebar component:

```
resetTable()
```

This refreshes the record list without full page re-render.

---

# Phase 11 — Developer Experience

Goal: developers **do not change their resources**.

Normal resource example:

```
class PostResource extends Resource
{
    use HasPanelist;

    public static function form(Form $form): Form
    {
        return $form->schema([...]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([...]);
    }
}
```

Plugin automatically transforms UI.

---

# Phase 12 — Optional Enhancements (Future)

These features can be added later.

## Preview Panel

3-panel layout:

```
list | preview | editor
```

Useful for:

* CMS content
* products
* user profiles

---

## Record Highlighting

Highlight active record in sidebar.

---

## Optimistic Sidebar Updates

Update list without table refresh.

---

## Panelist Persistence

Remember:

* last selected record
* panel width
* scroll position

---

# Phase 13 — Testing Plan

Test scenarios:

### Resource Compatibility

* basic resource
* soft deletes
* filters
* bulk actions

### Editing Flow

* select record
* create record
* save record
* validation errors

### Navigation

* deep link record
* browser refresh
* browser back button

---

# Phase 14 — Release Strategy

Initial release:

```
v0.1
```

Features:

* panelist layout
* sidebar table
* record editor
* resource integration
* config
* URL state

Future versions:

```
v0.2 preview panel
v0.3 keyboard navigation
v1.0 stable release
```

---

# Final Outcome

The plugin delivers a **panelist-style CRUD interface** that:

* works with existing resources
* supports Filament tables and forms
* eliminates page navigation
* feels like a modern editing interface

End result:

```
+----------------------+----------------------+
| resource list        | record editor        |
| search + filters     | form fields          |
| create action        | save actions         |
|                      |                      |
+----------------------+----------------------+
```
