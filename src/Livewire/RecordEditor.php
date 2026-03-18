<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Livewire;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * @property-read Schema $form
 */
class RecordEditor extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * Fully-qualified resource class name passed from the panelist page.
     *
     * @var class-string
     */
    public string $resource;

    /**
     * The ID of the currently loaded record, or null in create mode.
     */
    public int | string | null $recordId = null;

    /**
     * Whether the editor is in an active state (editing or creating).
     * Distinguishes from the initial empty state where nothing is selected.
     */
    public bool $isActive = false;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    protected ?Model $record = null;

    public function mount(int | string | null $initialRecordId = null): void
    {
        $this->form->fill();

        if ($initialRecordId) {
            $this->loadRecord($initialRecordId);
        }
    }

    public function form(Schema $schema): Schema
    {
        /** @var class-string $resource */
        $resource = $this->resource;

        return $resource::form(
            $schema
                ->statePath('data')
                ->model($this->getRecord() ?? $resource::getModel())
                ->operation($this->recordId ? 'edit' : 'create')
        );
    }

    public function loadRecord(int | string $id): void
    {
        $this->recordId = $id;
        $this->isActive = true;
        $this->record = null;

        /** @var class-string $resource */
        $resource = $this->resource;

        $record = $resource::getModel()::find($id);

        if (! $record) {
            return;
        }

        $this->record = $record;
        $this->form->fill($record->attributesToArray());
    }

    public function createRecord(): void
    {
        $this->recordId = null;
        $this->isActive = true;
        $this->record = null;
        $this->form->fill();
    }

    public function cancel(): void
    {
        $this->recordId = null;
        $this->isActive = false;
        $this->record = null;
        $this->form->fill();
        $this->dispatch('panelist-record-cancelled');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var class-string $resource */
        $resource = $this->resource;

        if ($this->recordId) {
            $record = $this->getRecord();

            if (! $record instanceof Model) {
                return;
            }

            $record->update($data);

            $this->dispatch('panelist-record-saved', id: $this->recordId);

            Notification::make()
                ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
                ->success()
                ->send();
        } else {
            $record = $resource::getModel()::create($data);

            /** @var int|string $key */
            $key = $record->getKey();

            $this->recordId = $key;
            $this->record = $record;

            $this->dispatch('panelist-record-saved', id: $this->recordId);

            Notification::make()
                ->title(__('filament-panels::resources/pages/create-record.notifications.created.title'))
                ->success()
                ->send();
        }
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('filament-actions::delete.single.label'))
            ->color('danger')
            ->icon('heroicon-m-trash')
            ->size(Size::Small)
            ->requiresConfirmation()
            ->modalHeading(__('filament-actions::delete.single.modal.heading', ['label' => $this->getModelLabel()]))
            ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
            ->action(function (): void {
                $record = $this->getRecord();

                if (! $record instanceof Model) {
                    return;
                }

                $id = $this->recordId;

                $record->delete();

                $this->recordId = null;
                $this->isActive = false;
                $this->record = null;
                $this->form->fill();

                $this->dispatch('panelist-record-deleted', id: $id);

                Notification::make()
                    ->title(__('filament-actions::delete.single.notifications.deleted.title'))
                    ->success()
                    ->send();
            });
    }

    public function getNavigationIcon(): string | BackedEnum | \Illuminate\Contracts\Support\Htmlable
    {
        /** @var class-string $resource */
        $resource = $this->resource;

        return $resource::getNavigationIcon() ?? 'heroicon-o-rectangle-stack';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('panelist::livewire.record-editor');
    }

    protected function getModelLabel(): string
    {
        /** @var class-string $resource */
        $resource = $this->resource;

        return $resource::getModelLabel();
    }

    protected function getRecord(): ?Model
    {
        if ($this->record instanceof Model) {
            return $this->record;
        }

        if (! $this->recordId) {
            return null;
        }

        /** @var class-string $resource */
        $resource = $this->resource;

        $this->record = $resource::getModel()::find($this->recordId);

        return $this->record;
    }
}
