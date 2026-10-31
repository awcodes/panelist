<?php

declare(strict_types=1);

use Awcodes\Panelist\Livewire\RecordEditor;
use Awcodes\Panelist\Panelist;
use Awcodes\Panelist\Tests\Fixtures\CustomPostEditor;
use Awcodes\Panelist\Tests\Fixtures\ManagePostsWithCustomEditor;
use Awcodes\Panelist\Tests\Fixtures\ManagePostsWithInvalidEditor;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\PostResource;
use Workbench\App\Filament\Resources\PostResource\Pages\ManagePosts;
use Workbench\App\Models\Post;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\UserFactory;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(UserFactory::new()->create());
});

function customEditor(): Testable
{
    return Livewire::test(CustomPostEditor::class, ['resource' => PostResource::class]);
}

describe('registration', function (): void {
    it('uses the default editor unless a page overrides it', function (): void {
        expect(ManagePosts::getEditor())->toBe(RecordEditor::class)
            ->and(ManagePostsWithCustomEditor::getEditor())->toBe(CustomPostEditor::class);
    });

    it('registers a custom editor so Livewire can resolve it on later requests', function (): void {
        // Livewire 3 (Filament 4) cannot resolve an unregistered class on update requests, so the editor must be
        // registered by class. The registry differs between Livewire 3 and 4, so the call itself is asserted.
        Livewire::partialMock()->shouldReceive('component')->once()->with(CustomPostEditor::class);

        app(Panelist::class)->registerEditorComponent(ManagePostsWithCustomEditor::class);
    });

    it('does not re-register the default editor', function (): void {
        Livewire::partialMock()->shouldNotReceive('component');

        app(Panelist::class)->registerEditorComponent(ManagePosts::class);
    });

    it('rejects an editor that does not extend RecordEditor', function (): void {
        app(Panelist::class)->registerEditorComponent(ManagePostsWithInvalidEditor::class);
    })->throws(InvalidArgumentException::class);
});

describe('hooks', function (): void {
    it('mutates data before filling the form', function (): void {
        $post = PostFactory::new()->create(['title' => 'quiet title']);

        customEditor()
            ->call('loadRecord', $post->getKey())
            ->assertSet('data.title', 'QUIET TITLE');
    });

    it('mutates data and calls hooks when saving', function (): void {
        $post = PostFactory::new()->create();

        customEditor()
            ->call('loadRecord', $post->getKey())
            ->set('data.title', 'a new title')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('calledHooks', ['beforeSave', 'afterSave']);

        expect($post->refresh()->title)->toBe('A New Title');
    });

    it('mutates data and calls hooks when creating', function (): void {
        customEditor()
            ->call('createRecord')
            ->set('data.title', 'Fresh')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('calledHooks', ['beforeCreate', 'afterCreate']);

        expect(Post::query()->where('title', 'Fresh')->sole()->content)->toBe('Created by a custom editor.');
    });

    it('stops saving when a hook halts', function (): void {
        $post = PostFactory::new()->create(['title' => 'Kept']);

        customEditor()
            ->call('loadRecord', $post->getKey())
            ->set('data.title', 'halt')
            ->call('save')
            ->assertSet('calledHooks', ['beforeSave'])
            ->assertNotDispatched('panelist-record-saved');

        expect($post->refresh()->title)->toBe('Kept');
    });

    it('calls hooks when deleting', function (): void {
        $post = PostFactory::new()->create();

        customEditor()
            ->call('loadRecord', $post->getKey())
            ->callAction('delete')
            ->assertSet('calledHooks', ['afterDelete']);
    });
});

it('mounts the page editor in the panelist layout', function (): void {
    Livewire::test(ManagePostsWithCustomEditor::class)
        ->assertSeeLivewire(CustomPostEditor::class);
});
