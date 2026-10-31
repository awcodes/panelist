<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Commands;

use Awcodes\Panelist\Pages\PanelistPage;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\search;

#[AsCommand(name: 'make:panelist-page')]
class MakePanelistPageCommand extends Command
{
    /** @var string */
    protected $signature = 'make:panelist-page
        {resource? : The resource class, either fully qualified or its class name}
        {--name= : The page class name (defaults to Manage{PluralModel})}
        {--F|force : Overwrite the page if it already exists}';

    /** @var string */
    protected $description = 'Create a Panelist page for a Filament resource';

    public function handle(Filesystem $files): int
    {
        $resource = $this->resolveResource();

        if ($resource === null) {
            return self::FAILURE;
        }

        $reflection = new ReflectionClass($resource);
        $resourceFile = (string) $reflection->getFileName();
        $resourceName = $reflection->getShortName();

        // Filament v4+ puts pages beside the resource; v3-style resources nest them under a directory named after it.
        $nestedDirectory = dirname($resourceFile) . DIRECTORY_SEPARATOR . $resourceName;
        $usesNestedLayout = $files->isDirectory($nestedDirectory . DIRECTORY_SEPARATOR . 'Pages');

        $namespace = $reflection->getNamespaceName() . ($usesNestedLayout ? "\\{$resourceName}" : '') . '\\Pages';
        $directory = ($usesNestedLayout ? $nestedDirectory : dirname($resourceFile)) . DIRECTORY_SEPARATOR . 'Pages';

        $class = $this->option('name')
            ?: 'Manage' . Str::pluralStudly(class_basename($resource::getModel()));
        $path = $directory . DIRECTORY_SEPARATOR . $class . '.php';

        if ($files->exists($path) && ! $this->option('force')) {
            $this->components->error("The page [{$path}] already exists. Use --force to overwrite it.");

            return self::FAILURE;
        }

        $imports = [$resource, PanelistPage::class];
        sort($imports, SORT_STRING | SORT_FLAG_CASE);

        $files->ensureDirectoryExists($directory);
        $files->put($path, str_replace(
            ['{{ namespace }}', '{{ imports }}', '{{ resourceClass }}', '{{ class }}'],
            [$namespace, implode(PHP_EOL, array_map(fn (string $import): string => "use {$import};", $imports)), $resourceName, $class],
            $files->get($this->getStubPath()),
        ));

        $this->components->info("Panelist page [{$path}] created.");
        $this->components->bulletList([
            "Register it as the index page in {$resourceName}::getPages():",
            "'index' => Pages\\{$class}::route('/'),",
        ]);

        return self::SUCCESS;
    }

    /**
     * @return class-string<resource>|null
     */
    protected function resolveResource(): ?string
    {
        $resources = $this->getPanelResources();
        $input = $this->argument('resource');

        if (! is_string($input) || $input === '') {
            if ($resources === []) {
                $this->components->error('No Filament resources are registered. Pass the resource class explicitly.');

                return null;
            }

            /** @var class-string<resource> */
            return search(
                label: 'Which resource should the Panelist page belong to?',
                options: fn (string $value): array => array_values(array_filter(
                    $resources,
                    fn (string $resource): bool => $value === '' || Str::contains($resource, $value, ignoreCase: true),
                )),
            );
        }

        $input = ltrim($input, '\\');

        if (! class_exists($input)) {
            $matches = array_values(array_filter(
                $resources,
                fn (string $resource): bool => class_basename($resource) === $input,
            ));

            if (count($matches) !== 1) {
                $this->components->error(count($matches) > 1
                    ? "The resource name [{$input}] is ambiguous. Pass the fully qualified class name."
                    : "Unable to find the resource [{$input}].");

                return null;
            }

            $input = $matches[0];
        }

        if (! is_subclass_of($input, Resource::class)) {
            $this->components->error("[{$input}] is not a Filament resource.");

            return null;
        }

        return $input;
    }

    /**
     * @return array<class-string<resource>>
     */
    protected function getPanelResources(): array
    {
        $resources = [];

        foreach (Filament::getPanels() as $panel) {
            $resources = [...$resources, ...$panel->getResources()];
        }

        return array_values(array_unique($resources));
    }

    protected function getStubPath(): string
    {
        $published = base_path('stubs/panelist/panelist-page.stub');

        return file_exists($published) ? $published : __DIR__ . '/../../stubs/panelist-page.stub';
    }
}
