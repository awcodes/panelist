<?php

declare(strict_types=1);

use Awcodes\Panelist\Tests\Fixtures\PostPolicy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\PostResource;
use Workbench\App\Filament\Resources\PostResource\Pages\ManagePosts;
use Workbench\App\Models\Post;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\UserFactory;

beforeEach(function (): void {
    PostPolicy::$denied = [];
    Gate::policy(Post::class, PostPolicy::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(UserFactory::new()->create());
});

describe('url state', function (): void {
    it('reads the selected record from the query string', function (): void {
        $post = PostFactory::new()->create();

        Livewire::withQueryParams(['record' => $post->getKey()])
            ->test(ManagePosts::class)
            ->assertSet('activeRecordId', (string) $post->getKey());
    });

    it('opens the deep-linked record in the editor and highlights it', function (): void {
        $post = PostFactory::new()->create(['title' => 'Deep linked']);

        $this->get(PostResource::getUrl('index', ['record' => $post->getKey()]))
            ->assertOk()
            ->assertSee(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->assertSee('panelist-selected-record');
    });

    it('shows the empty state without a deep link', function (): void {
        $this->get(PostResource::getUrl('index'))
            ->assertOk()
            ->assertSee(__('panelist::panelist.select_or_create'));
    });

    it('tracks the selected record', function (): void {
        Livewire::test(ManagePosts::class)
            ->dispatch('panelist-record-selected', id: 5)
            ->assertSet('activeRecordId', '5');
    });

    it('tracks a newly saved record', function (): void {
        Livewire::test(ManagePosts::class)
            ->dispatch('panelist-record-saved', id: 7)
            ->assertSet('activeRecordId', '7');
    });

    it('clears the record when editing is cancelled or the record is deleted', function (string $event): void {
        Livewire::withQueryParams(['record' => 3])
            ->test(ManagePosts::class)
            ->dispatch($event, id: 3)
            ->assertSet('activeRecordId', '');
    })->with(['panelist-record-cancelled', 'panelist-record-deleted']);
});

describe('page access', function (): void {
    it('forbids the page without viewAny permission', function (): void {
        PostPolicy::$denied = ['viewAny'];

        $this->get(PostResource::getUrl('index'))->assertForbidden();
    });

    it('offers the create action when creating is allowed', function (): void {
        Livewire::test(ManagePosts::class)
            ->assertActionVisible('create')
            ->callAction('create')
            ->assertDispatched('panelist-create-record');
    });

    it('hides the create action when creating is forbidden', function (): void {
        PostPolicy::$denied = ['create'];

        Livewire::test(ManagePosts::class)->assertActionHidden('create');
    });
});
