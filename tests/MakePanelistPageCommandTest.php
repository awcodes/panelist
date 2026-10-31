<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Workbench\App\Filament\Resources\PostResource;

beforeEach(function (): void {
    $this->path = dirname((string) (new ReflectionClass(PostResource::class))->getFileName())
        . '/PostResource/Pages/GeneratedPanelistPage.php';
});

afterEach(function (): void {
    app(Filesystem::class)->delete($this->path);
});

it('generates a panelist page for a resource given by class name', function (): void {
    $this->artisan('make:panelist-page', ['resource' => 'PostResource', '--name' => 'GeneratedPanelistPage'])
        ->assertSuccessful();

    expect(file_get_contents($this->path))
        ->toContain('namespace Workbench\App\Filament\Resources\PostResource\Pages;')
        ->toContain("use Awcodes\\Panelist\\Pages\\PanelistPage;\nuse Workbench\\App\\Filament\\Resources\\PostResource;")
        ->toContain('class GeneratedPanelistPage extends PanelistPage')
        ->toContain('protected static string $resource = PostResource::class;');
});

it('refuses to overwrite an existing page without --force', function (): void {
    $arguments = ['resource' => PostResource::class, '--name' => 'GeneratedPanelistPage'];

    $this->artisan('make:panelist-page', $arguments)->assertSuccessful();
    $this->artisan('make:panelist-page', $arguments)->assertFailed();
    $this->artisan('make:panelist-page', [...$arguments, '--force' => true])->assertSuccessful();
});

it('fails for an unknown resource', function (): void {
    $this->artisan('make:panelist-page', ['resource' => 'MissingResource'])->assertFailed();

    expect(file_exists($this->path))->toBeFalse();
});
