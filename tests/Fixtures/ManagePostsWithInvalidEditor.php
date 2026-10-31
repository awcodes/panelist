<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Tests\Fixtures;

use Awcodes\Panelist\Livewire\SidebarTable;
use Awcodes\Panelist\Pages\PanelistPage;
use Workbench\App\Filament\Resources\PostResource;

class ManagePostsWithInvalidEditor extends PanelistPage
{
    protected static string $resource = PostResource::class;

    protected static string $editor = SidebarTable::class;
}
