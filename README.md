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

### 2. Add the Trait to a Resource

Add `HasPanelist` to any Filament resource to replace its standard list page with the two-panel layout:

```php
use Awcodes\Panelist\Concerns\HasPanelist;
use Filament\Resources\Resource;

class PostResource extends Resource
{
    use HasPanelist;

    protected static ?string $model = Post::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            Textarea::make('content')->rows(8),
            Toggle::make('is_published')->label('Published'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable(),
            IconColumn::make('is_published')->boolean(),
            TextColumn::make('created_at')->dateTime(),
        ]);
    }
}
```

That's it. Navigating to the resource will now render the panelist layout instead of the standard Filament list view. Your existing `form()` and `table()` definitions work unchanged.

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

## Keyboard Shortcuts

When keyboard shortcuts are enabled (the default), the following bindings are available:

| Shortcut         | Action                  |
|------------------|-------------------------|
| `Cmd / Ctrl + N` | Create a new record     |
| `Cmd / Ctrl + S` | Save the current record |
| `Escape`         | Cancel editing          |

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
