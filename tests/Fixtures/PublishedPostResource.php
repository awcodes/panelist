<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Filament\Resources\PostResource;

/**
 * Stands in for any query scoping (tenancy, ownership) by hiding unpublished posts.
 */
class PublishedPostResource extends PostResource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_published', true);
    }
}
