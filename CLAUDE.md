# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About This Project

`awcodes/panelist` is a Filament 4/5 plugin that transforms standard resource CRUD interfaces into a Gmail/Notion/Linear-style master-detail (panelist) editing experience — a sidebar list on the left, a form editor on the right.

## Versioning

Released packages follow semver: `1.x` is the active branch and the docs version on docs.aw.codes. Public API changes (the `PanelistPage` and `RecordEditor` extension points, config keys, the `make:panelist-page` command, event names) need a major release.

## Commands

### PHP

```bash
composer test           # Full suite: refactor check → lint check → types → unit tests
composer test:unit      # Run Pest tests only
composer test:lint      # Check code style (Pint --test, no writes)
composer test:types     # PHPStan static analysis
composer test:refactor  # Rector dry-run

composer lint           # Fix code style with Pint
composer refactor       # Apply Rector fixes
```

### Frontend

```bash
npm run build  # Compile the workbench theme (workbench/resources/css/filament/admin/theme.css) with Tailwind
```

### Workbench (local dev app)

```bash
composer build   # Build the workbench app (Filament assets, fresh seeded DB) and compile its theme
composer serve   # Build + serve workbench at localhost
composer clear   # Clean testbench skeleton
composer prepare # Prepare testbench
```

### Running a single test

```bash
vendor/bin/pest tests/ExampleTest.php
vendor/bin/pest --filter "test name"
```

## Architecture

### Package Structure

This is a standard Laravel package using Orchestra Testbench for development/testing. The `workbench/` directory is a local Laravel app used for manual testing and demos.

### Core Classes

- **`PanelistPlugin`** — Implements Filament's `Plugin` interface. Registered in a Filament panel via `->plugin(PanelistPlugin::make())`. Handles asset/view/translation registration and holds plugin-level configuration.
- **`PanelistServiceProvider`** — Registers views, config, translations, the `make:panelist-page` command and the Livewire components, and publishes the page stub. The entry point for Laravel's package auto-discovery.
- **`Panelist`** (and its facade) — Registers each Panelist page's editor component with Livewire after boot.

### Integration Pattern

Each resource gets its own page class extending the abstract `Pages\PanelistPage` (Filament reads the resource from the page's static `$resource`), registered as the resource's `index` page. `make:panelist-page` generates it from `stubs/panelist-page.stub`. Never generate page classes at runtime (e.g. `eval()`): Filament's component and route caches skip `getPages()`, so cached classes must be autoloadable from disk — `tests/PanelistPageTest.php` enforces this. The layout is:
- **Left panel** — Livewire `SidebarTable` component (list, search, filters, create action)
- **Right panel** — Livewire `RecordEditor` component (form, save actions). A page can swap in a subclass via `protected static string $editor`; `Panelist::registerEditorComponents()` registers these with Livewire after boot, which Livewire 3 (Filament 4) needs to resolve them on update requests. Customization hooks (`mutateFormData*`, `handleRecord*`, `before*`/`after*`) mirror Filament's create/edit pages and live on the editor, not the page.

Panels communicate via Livewire events (`panelist-record-selected`, `panelist-record-saved`).

### Frontend Assets

- `resources/css/index.css` — Tailwind source the consuming app imports into its Filament theme (see README); the package ships no compiled CSS or JS
- Keyboard shortcuts and the resizable layout are inline Alpine in `resources/views/pages/panelist.blade.php`
- The workbench theme (`workbench/resources/css/filament/admin/theme.css`) imports `index.css` the same way a consumer does and is served by the `workbench.theme` route

### Testing Setup

- Tests use Orchestra Testbench + Pest + Livewire testing support
- `tests/TestCase.php` extends Orchestra Testbench's `TestCase` with `WithWorkbench`, so tests run against the workbench panel, models and factories
- `tests/Fixtures/` holds a toggleable `PostPolicy`, a scoped `PublishedPostResource` and a custom editor used to test authorization, scoping and editor hooks
- `tests/DebugTest.php` enforces no `dd()`/`dump()` left in source code

### Code Quality

- PHPStan at level 5 with Larastan over `src`, `config` and `workbench`
- Pint with Laravel preset (custom rules in `pint.json`)
- Rector over `src` and `workbench`
- CI runs the shared `awcodes/.github` Filament plugin workflow (Filament 4 and 5); `composer.lock` is not committed
