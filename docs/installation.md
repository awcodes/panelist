---
title: Installation
description: Install Panelist and add its styles to your Filament theme.
---

# Installation

## Compatibility

| Filament version | Package version |
|------------------|-----------------|
| 4.x & 5.x        | 1.x             |

Panelist requires PHP 8.2 or later and `filament/filament`.

## Install the package

Install the package via Composer:

```bash
composer require awcodes/panelist
```

The service provider is registered automatically through Laravel's package discovery.

## Adding Panelist to your theme

Panelist's layout is styled with Tailwind classes, so your theme has to compile Panelist's stylesheet and scan its Blade views for the classes they use. Without this step, the layout and the selected and highlighted rows are not styled correctly.

> [!IMPORTANT]
> If you are using Filament Panels and have not set up a custom theme, follow the instructions in the [Filament documentation](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) before continuing.

Once you have a custom theme, import Panelist's stylesheet and add its views as a source in your theme's CSS file — or in your application's CSS file if you are using the standalone Filament packages:

```css
@import '../../../../vendor/awcodes/panelist/resources/css/index.css';

@source '../../../../vendor/awcodes/panelist/resources/**/*.blade.php';
```

Then rebuild your assets so the new styles are compiled in.

## Publishing the config

To change the defaults described in [Configuration](configuration.md), publish the config file:

```bash
php artisan vendor:publish --tag="panelist-config"
```

## Publishing the views and translations

Publishing the views is optional, and only needed if you want to change the layout's markup:

```bash
php artisan vendor:publish --tag="panelist-views"
```

The page title, the editor's empty-state message and the notice shown for an unavailable record are translation strings. To change their wording or add a language, publish the translations:

```bash
php artisan vendor:publish --tag="panelist-translations"
```
