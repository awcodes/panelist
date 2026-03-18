<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Concerns;

use Awcodes\Panelist\Pages\PanelistPage;
use Filament\Resources\Pages\PageRegistration;

trait HasPanelist
{
    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => static::getPanelistPageClass()::route('/'),
        ];
    }

    /**
     * Returns a unique PanelistPage subclass bound to this resource.
     * A dynamic class is generated once per resource and cached.
     *
     * @return class-string<PanelistPage>
     */
    public static function getPanelistPageClass(): string
    {
        $resource = static::class;
        $className = 'PanelistPageFor' . str_replace('\\', '', $resource);

        if (! class_exists($className)) {
            // phpcs:ignore
            eval("class {$className} extends \\Awcodes\\Panelist\\Pages\\PanelistPage { protected static string \$resource = '{$resource}'; }"); // @phpstan-ignore-line
        }

        return $className;
    }
}
