<?php

declare(strict_types=1);

namespace Awcodes\Panelist;

use Awcodes\Panelist\Commands\MakePanelistPageCommand;
use Awcodes\Panelist\Livewire\RecordEditor;
use Awcodes\Panelist\Livewire\SidebarTable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PanelistServiceProvider extends PackageServiceProvider
{
    public static string $name = 'panelist';

    public static string $viewNamespace = 'panelist';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews(static::$viewNamespace)
            ->hasCommand(MakePanelistPageCommand::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('awcodes/panelist');
            });
    }

    public function packageBooted(): void
    {
        Livewire::component('panelist.sidebar-table', SidebarTable::class);
        Livewire::component('panelist.record-editor', RecordEditor::class);

        // Panels and their resources are only fully registered once the application has booted.
        $this->app->booted(fn () => $this->app->make(Panelist::class)->registerEditorComponents());

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../stubs/panelist-page.stub' => base_path('stubs/panelist/panelist-page.stub'),
            ], 'panelist-stubs');
        }
    }
}
