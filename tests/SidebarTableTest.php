<?php

declare(strict_types=1);

use Awcodes\Panelist\Livewire\SidebarTable;
use Awcodes\Panelist\Tests\Fixtures\PublishedPostResource;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\PostResource;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\UserFactory;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(UserFactory::new()->create());
});

function sidebar(string $resource = PostResource::class, int | string | null $selectedRecordId = null): Testable
{
    return Livewire::test(SidebarTable::class, ['resource' => $resource, 'selectedRecordId' => $selectedRecordId]);
}

it('lists the records of the resource query', function (): void {
    $published = PostFactory::new()->times(2)->create(['is_published' => true]);
    $drafts = PostFactory::new()->times(2)->create(['is_published' => false]);

    sidebar(PublishedPostResource::class)
        ->assertCanSeeTableRecords($published)
        ->assertCanNotSeeTableRecords($drafts);
});

it('uses the resource table columns and search', function (): void {
    $match = PostFactory::new()->create(['title' => 'Needle']);
    $other = PostFactory::new()->create(['title' => 'Haystack']);

    sidebar()
        ->assertTableColumnExists('title')
        ->searchTable('Needle')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);
});

it('selects a record when its row is opened', function (): void {
    $post = PostFactory::new()->create();

    sidebar()
        ->callTableAction('openRecord', $post)
        ->assertSet('selectedRecordId', $post->getKey())
        ->assertDispatched('panelist-record-selected', id: $post->getKey());
});

it('highlights the selected record', function (): void {
    $post = PostFactory::new()->create();

    sidebar()
        ->callTableAction('openRecord', $post)
        ->assertSeeHtml('panelist-selected-record');
});

it('highlights a record selected from a deep link', function (): void {
    $post = PostFactory::new()->create();

    // Keys from the query string arrive as strings.
    sidebar(selectedRecordId: (string) $post->getKey())
        ->assertSeeHtml('panelist-selected-record');
});

it('highlights nothing when no record is selected', function (): void {
    PostFactory::new()->create();

    sidebar()->assertDontSeeHtml('panelist-selected-record');
});

it('selects the saved record and refreshes after a save', function (): void {
    $post = PostFactory::new()->create();

    sidebar()
        ->dispatch('panelist-record-saved', id: $post->getKey())
        ->assertSet('selectedRecordId', $post->getKey())
        ->assertSeeHtml('panelist-selected-record');
});

it('shows a record created after the table first rendered', function (): void {
    $component = sidebar();

    $post = PostFactory::new()->create(['title' => 'Late arrival']);

    $component
        ->dispatch('panelist-record-saved', id: $post->getKey())
        ->assertCanSeeTableRecords([$post]);
});

it('clears the selection and drops the record after a delete', function (): void {
    $post = PostFactory::new()->create();

    $component = sidebar()->callTableAction('openRecord', $post);

    $post->delete();

    $component
        ->dispatch('panelist-record-deleted', id: $post->getKey())
        ->assertSet('selectedRecordId', null)
        ->assertCanNotSeeTableRecords([$post]);
});

it('clears the selection when editing is cancelled', function (): void {
    $post = PostFactory::new()->create();

    sidebar()
        ->callTableAction('openRecord', $post)
        ->dispatch('panelist-record-cancelled')
        ->assertSet('selectedRecordId', null)
        ->assertDontSeeHtml('panelist-selected-record');
});
