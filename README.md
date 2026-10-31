# Panelist for Filament

[![Latest Version](https://img.shields.io/github/release/awcodes/panelist.svg?style=flat-square&color=blue&label=Release)](https://github.com/awcodes/panelist/releases)
[![MIT Licensed](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/awcodes/panelist.svg?style=flat-square&color=blue&label=Downloads)](https://packagist.org/packages/awcodes/panelist)
[![GitHub Repo stars](https://img.shields.io/github/stars/awcodes/panelist?style=flat-square&color=blue&label=Stars)](https://github.com/awcodes/panelist/stargazers)
[![Filament Version](https://img.shields.io/badge/Filament-4.x-d97706.svg?style=flat-square)](https://filamentphp.com/docs/4.x/panels/installation)
[![Filament Version](https://img.shields.io/badge/Filament-5.x-d97706.svg?style=flat-square)](https://filamentphp.com/docs/5.x/panels/installation)

A Filament plugin that transforms standard resource CRUD interfaces into a Gmail/Notion/Linear-style master-detail editing experience — a scrollable record list on the left, a form editor on the right.

## Installation

Install via Composer:

```bash
composer require awcodes/panelist
```

> [!IMPORTANT]
> If you are using Filament Panels and have not set up a custom theme, follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme, add the plugin's views to your theme CSS file (or your app's CSS file for standalone packages):

```css
@import '../../../../vendor/awcodes/panelist/resources/css/index.css';

@source '../../../../vendor/awcodes/panelist/resources/**/*.blade.php';
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="panelist-config"
```

Optionally, publish the views:

```bash
php artisan vendor:publish --tag="panelist-views"
```

## Usage

### 1. Register the Plugin

Add `PanelistPlugin` to your Filament panel provider:

```php
use Awcodes\Panelist\PanelistPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            PanelistPlugin::make()
        ]);
}
```

### 2. Create a Panelist Page

Generate a Panelist page for a resource:

```bash
php artisan make:panelist-page PostResource
```

The command accepts the resource's class name or its fully qualified class name, and prompts you to pick one when you omit it. It writes the page into the resource's `Pages` directory, named `Manage{PluralModel}` by default (use `--name` to change it):

```php
namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use Awcodes\Panelist\Pages\PanelistPage;

class ManagePosts extends PanelistPage
{
    protected static string $resource = PostResource::class;
}
```

### 3. Register It as the Index Page

Point the resource's `index` page at the new class:

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ManagePosts::route('/'),
        'create' => Pages\CreatePost::route('/create'),
        'edit' => Pages\EditPost::route('/{record}/edit'),
    ];
}
```

Navigating to the resource now renders the panelist layout instead of the standard list page. Your existing `form()` and `table()` definitions work unchanged. Keeping the `create` and `edit` pages registered is optional, but it lets global search, notifications and other links that call `PostResource::getUrl('edit')` keep working.

### How It Works

- **Left panel** — displays your resource's table as a scrollable, searchable sidebar list. Clicking a row loads it into the editor.
- **Right panel** — displays your resource's form for editing the selected record, or a blank form for creating a new one.
- **URL sync** — the selected record's ID is persisted to the query string (`?record=123`) so links are bookmarkable.
- **Resizable** — drag the divider between panels to adjust the sidebar width. The width is remembered in `localStorage`.

## Configuration

```php
// config/panelist.php
return [
    'sidebar_width' => 320,                // Default sidebar width in pixels
    'enable_keyboard_shortcuts' => true,   // Enable keyboard shortcuts
];
```

You can also configure the plugin per-panel via the fluent API:

```php
PanelistPlugin::make()
    ->sidebarWidth(400)
    ->keyboardShortcuts(false)
```

## Customizing the Editor

The right-hand panel is a Livewire component, `Awcodes\Panelist\Livewire\RecordEditor`. To customize how records are filled and saved, extend it and point your page at the subclass:

```php
use Awcodes\Panelist\Livewire\RecordEditor;

class PostEditor extends RecordEditor
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        // ...
    }
}
```

```php
class ManagePosts extends PanelistPage
{
    protected static string $resource = PostResource::class;

    protected static string $editor = PostEditor::class;
}
```

Panelist registers the editor with Livewire automatically. The editor supports the same hooks as Filament's create and edit pages:

| Purpose | Methods |
|---|---|
| Mutate form data | `mutateFormDataBeforeFill()`, `mutateFormDataBeforeSave()`, `mutateFormDataBeforeCreate()` |
| Replace persistence | `handleRecordCreation()`, `handleRecordUpdate()` |
| Lifecycle hooks | `beforeFill()`, `afterFill()`, `beforeValidate()`, `afterValidate()`, `beforeSave()`, `afterSave()`, `beforeCreate()`, `afterCreate()`, `beforeDelete()`, `afterDelete()` |

Throw `Filament\Support\Exceptions\Halt` from a hook to stop the save.

The editor checks the resource's `canCreate()`, `canEdit()` and `canDelete()` authorization, and only loads records returned by the resource's `getEloquentQuery()`, so tenancy and other query scoping apply.

## Keyboard Shortcuts

When keyboard shortcuts are enabled (the default), the following bindings are available:

| Shortcut         | Action                                        |
|------------------|-----------------------------------------------|
| `↓` / `j`        | Move to the next record in the list           |
| `↑` / `k`        | Move to the previous record in the list       |
| `Enter` / `o`    | Open the highlighted record                   |
| `c`              | Create a new record                           |
| `Cmd / Ctrl + S` | Save the current record                       |
| `Escape`         | Close the editor without saving               |

Single-key shortcuts are ignored while you type in a field or while a modal is open, so they never interfere with editing. `Cmd / Ctrl + S` works from inside fields. `Escape` closes an open modal without closing the editor, and only closes the editor when focus is outside a field. `Enter` on a focused button presses that button. Browsers reserve `Cmd / Ctrl + N` for a new window, which is why creating uses `c`.

Disable them per panel with `->keyboardShortcuts(false)` or globally with the `enable_keyboard_shortcuts` config option.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Adam Weston](https://github.com/awcodes)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
