<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Tests\Fixtures;

use Awcodes\Panelist\Pages\PanelistPage;
use Workbench\App\Filament\Resources\PostResource;

class ManagePostsWithCustomEditor extends PanelistPage
{
    protected static string $resource = PostResource::class;

    protected static string $editor = CustomPostEditor::class;
}
