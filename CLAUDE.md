# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About This Project

`awcodes/panelist` is a Filament 4/5 plugin that transforms standard resource CRUD interfaces into a Gmail/Notion/Linear-style master-detail (panelist) editing experience — a sidebar list on the left, a form editor on the right.

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

### JavaScript

```bash
npm run dev    # Watch mode (esbuild)
npm run build  # Production build
```

### Workbench (local dev app)

```bash
composer build   # Build workbench assets
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
- **`PanelistServiceProvider`** — Registers views, config, migrations, and discovers commands. The entry point for Laravel's package auto-discovery.
- **`Facades/Panelist`** — Facade for the core `Panelist` class.

### Planned Integration Pattern

Resources opt-in via a `HasPanelist` trait. The plugin intercepts the resource's index page, replacing it with a two-panel layout:
- **Left panel** — Livewire `SidebarTable` component (list, search, filters, create action)
- **Right panel** — Livewire `RecordEditor` component (form, save actions)

Panels communicate via Livewire events (`panelist-record-selected`, `panelist-record-saved`).

### Frontend Assets

- `resources/css/index.css` — Imports Filament theme; compiled output goes to `resources/dist/`
- `resources/js/index.js` — JavaScript entry point
- `bin/build.js` — esbuild configuration (supports watch mode)

### Testing Setup

- Tests use Orchestra Testbench + Pest + Livewire testing support
- `tests/TestCase.php` extends `FilamentTestCase` with Livewire/Filament traits
- `tests/DebugTest.php` enforces no `dd()`/`dump()` left in source code

### Code Quality

- PHPStan at level 4 with Larastan + Octane compatibility checks
- Pint with Laravel preset (custom rules in `pint.json`)
- Rector targeting PHP 8.2+ patterns
