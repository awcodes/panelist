<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;

class SidebarTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    /**
     * Fully-qualified resource class name passed from the panelist page.
     *
     * @var class-string
     */
    public string $resource;

    public int | string | null $selectedRecordId = null;

    public function table(Table $table): Table
    {
        /** @var class-string $resource */
        $resource = $this->resource;

        $table = $resource::table(
            $table
                ->query(fn (): Builder => $resource::getEloquentQuery())
//                ->recordUrl(null)
                ->selectable(false)
                ->recordClasses(fn (Model $record): string => $this->isSelected($record) ? 'panelist-selected-record' : '')
        );

        return $table
            ->recordAction('openRecord')
            ->recordActions([
                Action::make('openRecord')
                    ->label(__('panelist::panelist.open_record'))
                    ->action(fn (Model $record) => $this->selectRecord($record->getKey())),
            ]);
    }

    public function isSelected(Model $record): bool
    {
        // Keys from the query string are strings while model keys are usually integers.
        return $this->selectedRecordId !== null
            && (string) $record->getKey() === (string) $this->selectedRecordId;
    }

    public function selectRecord(int | string $id): void
    {
        $this->selectedRecordId = $id;
        $this->dispatch('panelist-record-selected', id: $id);
    }

    public function createRecord(): void
    {
        $this->dispatch('panelist-create-record');
    }

    /**
     * Refresh the table after a record is saved.
     */
    #[On('panelist-record-saved')]
    public function refreshTable(int | string $id): void
    {
        $this->selectedRecordId = $id;
        $this->resetTable();
    }

    /**
     * Refresh the table after a record is deleted.
     */
    #[On('panelist-record-deleted')]
    public function refreshTableAfterDelete(): void
    {
        $this->selectedRecordId = null;
        $this->resetTable();
    }

    #[On('panelist-record-cancelled')]
    #[On('panelist-create-record')]
    public function clearSelection(): void
    {
        $this->selectedRecordId = null;
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('panelist::livewire.sidebar-table');
    }
}
