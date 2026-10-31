<?php

declare(strict_types=1);

use Awcodes\Panelist\Pages\PanelistPage;
use Composer\Autoload\ClassLoader;
use Filament\Facades\Filament;
use Workbench\App\Filament\Resources\PostResource;
use Workbench\App\Filament\Resources\PostResource\Pages\ManagePosts;
use Workbench\Database\Factories\UserFactory;

it('registers the panelist page as the resource index', function (): void {
    expect(PostResource::getPages()['index']->getPage())
        ->toBe(ManagePosts::class)
        ->and(is_subclass_of(ManagePosts::class, PanelistPage::class))->toBeTrue()
        ->and(ManagePosts::getResource())->toBe(PostResource::class);
});

it('only caches page components that the autoloader can load from disk', function (): void {
    // A component cache lets Filament skip calling getPages() on boot, so every cached
    // class must be autoloadable on its own rather than generated at runtime.
    $panel = Filament::getPanel('admin');
    $panel->cacheComponents();

    try {
        /** @var array{livewireComponents: array<string, class-string>} $cache */
        $cache = require $panel->getComponentCachePath();
    } finally {
        $panel->clearCachedComponents();
    }

    $loader = ClassLoader::getRegisteredLoaders()[array_key_first(ClassLoader::getRegisteredLoaders())];

    expect($cache['livewireComponents'])->toContain(ManagePosts::class);

    foreach ($cache['livewireComponents'] as $component) {
        expect($loader->findFile($component))
            ->not->toBeFalse("Cached component [{$component}] has no file the autoloader can find.");
    }
});

it('renders the panelist layout', function (): void {
    $this->actingAs(UserFactory::new()->create())
        ->get(PostResource::getUrl('index'))
        ->assertOk()
        ->assertSeeLivewire('panelist.sidebar-table')
        ->assertSeeLivewire('panelist.record-editor');
});
