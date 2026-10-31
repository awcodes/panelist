<?php

declare(strict_types=1);

use Awcodes\Panelist\Livewire\RecordEditor;
use Awcodes\Panelist\Tests\Fixtures\PostPolicy;
use Awcodes\Panelist\Tests\Fixtures\PublishedPostResource;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\PostResource;
use Workbench\App\Models\Post;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\TagFactory;
use Workbench\Database\Factories\UserFactory;

beforeEach(function (): void {
    PostPolicy::$denied = [];
    Gate::policy(Post::class, PostPolicy::class);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(UserFactory::new()->create());
});

function editor(string $resource = PostResource::class, int | string | null $initialRecordId = null): Testable
{
    return Livewire::test(RecordEditor::class, ['resource' => $resource, 'initialRecordId' => $initialRecordId]);
}

describe('loading', function (): void {
    it('loads a record into the form', function (): void {
        $post = PostFactory::new()->create(['title' => 'Hello']);

        editor()
            ->call('loadRecord', $post->getKey())
            ->assertSet('isActive', true)
            ->assertSet('recordId', $post->getKey())
            ->assertSet('data.title', 'Hello');
    });

    it('loads the initial record from a deep link', function (): void {
        $post = PostFactory::new()->create(['title' => 'Linked']);

        editor(initialRecordId: $post->getKey())
            ->assertSet('isActive', true)
            ->assertSet('data.title', 'Linked');
    });

    it('does not load a record outside the resource query scope', function (): void {
        $hidden = PostFactory::new()->create(['title' => 'Draft', 'is_published' => false]);

        editor(PublishedPostResource::class)
            ->call('loadRecord', $hidden->getKey())
            ->assertSet('isActive', false)
            ->assertSet('recordId', null)
            ->assertSet('data.title', null)
            ->assertNotified(__('panelist::panelist.record_unavailable'))
            ->assertDispatched('panelist-record-cancelled');
    });

    it('does not load a record outside the scope from a deep link', function (): void {
        $hidden = PostFactory::new()->create(['is_published' => false]);

        editor(PublishedPostResource::class, $hidden->getKey())
            ->assertSet('isActive', false)
            ->assertSet('recordId', null);
    });

    it('does not load a record the user may not edit', function (): void {
        PostPolicy::$denied = ['update'];
        $post = PostFactory::new()->create(['title' => 'Locked']);

        editor()
            ->call('loadRecord', $post->getKey())
            ->assertSet('isActive', false)
            ->assertSet('data.title', null)
            ->assertNotified(__('panelist::panelist.record_unavailable'));
    });

    it('treats a missing record as unavailable', function (): void {
        editor()
            ->call('loadRecord', 9999)
            ->assertSet('isActive', false)
            ->assertNotified(__('panelist::panelist.record_unavailable'));
    });
});

describe('saving', function (): void {
    it('updates an existing record', function (): void {
        $post = PostFactory::new()->create(['title' => 'Before']);

        editor()
            ->call('loadRecord', $post->getKey())
            ->set('data.title', 'After')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('panelist-record-saved', id: $post->getKey());

        expect($post->refresh()->title)->toBe('After');
    });

    it('creates a new record and switches to editing it', function (): void {
        $component = editor()
            ->call('createRecord')
            ->assertSet('isActive', true)
            ->set('data.title', 'Brand new')
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->where('title', 'Brand new')->sole();

        $component
            ->assertSet('recordId', $post->getKey())
            ->assertDispatched('panelist-record-saved', id: $post->getKey());
    });

    it('validates the form', function (): void {
        editor()
            ->call('createRecord')
            ->set('data.title', '')
            ->call('save')
            ->assertHasErrors(['data.title' => 'required']);

        expect(Post::query()->count())->toBe(0);
    });

    it('does not enter create mode when creating is forbidden', function (): void {
        PostPolicy::$denied = ['create'];

        editor()
            ->call('createRecord')
            ->assertSet('isActive', false);
    });

    it('refuses to create when creating is forbidden', function (): void {
        PostPolicy::$denied = ['create'];

        editor()
            ->set('isActive', true)
            ->set('data.title', 'Sneaky')
            ->call('save')
            ->assertForbidden();

        expect(Post::query()->count())->toBe(0);
    });

    it('refuses to update when editing becomes forbidden', function (): void {
        $post = PostFactory::new()->create(['title' => 'Before']);

        $component = editor()->call('loadRecord', $post->getKey());

        PostPolicy::$denied = ['update'];

        $component
            ->set('data.title', 'After')
            ->call('save')
            ->assertForbidden();

        expect($post->refresh()->title)->toBe('Before');
    });

    it('refuses to update a record outside the scope when its key is injected', function (): void {
        $hidden = PostFactory::new()->create(['title' => 'Draft', 'is_published' => false]);

        editor(PublishedPostResource::class)
            ->set('isActive', true)
            ->set('recordId', $hidden->getKey())
            ->set('data.title', 'Hijacked')
            ->call('save')
            ->assertForbidden();

        expect($hidden->refresh()->title)->toBe('Draft');
    });
});

describe('deleting', function (): void {
    it('deletes the loaded record', function (): void {
        $post = PostFactory::new()->create();

        editor()
            ->call('loadRecord', $post->getKey())
            ->callAction('delete')
            ->assertSet('isActive', false)
            ->assertSet('recordId', null)
            ->assertDispatched('panelist-record-deleted', id: $post->getKey());

        expect(Post::query()->find($post->getKey()))->toBeNull();
    });

    it('hides the delete action when deleting is forbidden', function (): void {
        PostPolicy::$denied = ['delete'];
        $post = PostFactory::new()->create();

        editor()
            ->call('loadRecord', $post->getKey())
            ->assertActionHidden('delete');

        expect(Post::query()->find($post->getKey()))->not->toBeNull();
    });
});

describe('relationships', function (): void {
    it('fills relationship fields when loading a record', function (): void {
        $tags = TagFactory::new()->times(2)->create();
        $post = PostFactory::new()->create();
        $post->tags()->attach($tags);

        editor()
            ->call('loadRecord', $post->getKey())
            ->assertSet('data.tags', fn (array $state): bool => collect($state)->map(fn ($id): int => (int) $id)->sort()->values()->all() === $tags->modelKeys());
    });

    it('saves relationships when creating a record', function (): void {
        $tags = TagFactory::new()->times(2)->create();

        editor()
            ->call('createRecord')
            ->set('data.title', 'Tagged')
            ->set('data.tags', $tags->modelKeys())
            ->call('save')
            ->assertHasNoErrors();

        expect(Post::query()->where('title', 'Tagged')->sole()->tags->modelKeys())
            ->toEqualCanonicalizing($tags->modelKeys());
    });

    it('saves relationships when updating a record', function (): void {
        [$kept, $removed, $added] = TagFactory::new()->times(3)->create()->all();
        $post = PostFactory::new()->create();
        $post->tags()->attach([$kept->getKey(), $removed->getKey()]);

        editor()
            ->call('loadRecord', $post->getKey())
            ->set('data.tags', [$kept->getKey(), $added->getKey()])
            ->call('save')
            ->assertHasNoErrors();

        expect($post->refresh()->tags->modelKeys())
            ->toEqualCanonicalizing([$kept->getKey(), $added->getKey()]);
    });

    it('rolls back a failed save when the panel uses database transactions', function (): void {
        Filament::getPanel('admin')->databaseTransactions();
        Post::created(fn () => throw new RuntimeException('Failed after insert.'));

        try {
            editor()
                ->call('createRecord')
                ->set('data.title', 'Orphan')
                ->call('save');
        } catch (RuntimeException) {
            // Expected: the failure must propagate after the transaction rolls back.
        }

        expect(Post::query()->where('title', 'Orphan')->exists())->toBeFalse();
    });
});
