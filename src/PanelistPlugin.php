<?php

declare(strict_types=1);

namespace Awcodes\Panelist;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PanelistPlugin implements Plugin
{
    protected ?bool $keyboardShortcuts = null;

    protected ?int $sidebarWidth = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'panelist';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function keyboardShortcuts(bool $enabled = true): static
    {
        $this->keyboardShortcuts = $enabled;

        return $this;
    }

    public function sidebarWidth(int $pixels): static
    {
        $this->sidebarWidth = $pixels;

        return $this;
    }

    public function hasKeyboardShortcuts(): bool
    {
        return $this->keyboardShortcuts ?? (bool) config('panelist.enable_keyboard_shortcuts', true);
    }

    public function getSidebarWidth(): int
    {
        return $this->sidebarWidth ?? (int) config('panelist.sidebar_width', 320);
    }
}
