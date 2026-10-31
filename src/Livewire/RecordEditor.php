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
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Throwable;

/**
 * @property-read Schema $form
 */
class RecordEditor extends Component implements HasActions, HasForms
{
    use CanUseDatabaseTransactions;
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * Fully-qualified resource class name passed from the panelist page.
     *
     * @var class-string<resource>
     */
    public string $resource;

    /**
     * The key of the currently loaded record, or null in create mode.
     */
    public int | string | null $recordId = null;

    /**
     * Whether the editor is editing or creating, as opposed to the initial empty state.
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

        if (filled($initialRecordId)) {
            $this->loadRecord($initialRecordId);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $this->resource::form(
            $schema
                ->statePath('data')
                ->model($this->getRecord() ?? $this->resource::getModel())
                ->operation($this->recordId === null ? 'create' : 'edit')
        );
    }

    public function loadRecord(int | string $id): void
    {
        $record = $this->resolveRecord($id);

        // Out-of-scope and forbidden records get the same response, so the editor never reveals which records exist.
        if (! $record instanceof Model || ! $this->resource::canEdit($record)) {
            $this->resetEditor();
            $this->dispatch('panelist-record-cancelled');

            Notification::make()
                ->title(__('panelist::panelist.record_unavailable'))
                ->danger()
                ->send();

            return;
        }

        $this->recordId = $id;
        $this->record = $record;
        $this->isActive = true;

        // The form schema is cached for the request, so it must be pointed at the new record before filling.
        $this->form->model($record)->operation('edit');

        $this->callHook('beforeFill');
        $this->form->fill($this->mutateFormDataBeforeFill($record->attributesToArray()));
        $this->callHook('afterFill');
    }

    public function createRecord(): void
    {
        if (! $this->resource::canCreate()) {
            return;
        }

        $this->resetEditor();
        $this->isActive = true;
    }

    public function cancel(): void
    {
        $this->resetEditor();
        $this->dispatch('panelist-record-cancelled');
    }

    public function save(): void
    {
        if (! $this->isActive) {
            return;
        }

        $existingRecord = $this->getRecord();
        $isCreating = $this->recordId === null;

        abort_unless(
            $isCreating
                ? $this->resource::canCreate()
                : $existingRecord instanceof Model && $this->resource::canEdit($existingRecord),
            403,
        );

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            if ($existingRecord instanceof Model) {
                // With an existing record as the form model, getState() also saves relationships.
                $data = $this->form->getState(afterValidate: function (): void {
                    $this->callHook('afterValidate');
                    $this->callHook('beforeSave');
                });

                $record = $this->handleRecordUpdate($existingRecord, $this->mutateFormDataBeforeSave($data));

                $this->callHook('afterSave');
            } else {
                $data = $this->form->getState();

                $this->callHook('afterValidate');

                $data = $this->mutateFormDataBeforeCreate($data);

                $this->callHook('beforeCreate');

                $record = $this->handleRecordCreation($data);

                $this->form->model($record)->saveRelationships();

                $this->callHook('afterCreate');
            }
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        /** @var int|string $key */
        $key = $record->getKey();

        $this->recordId = $key;
        $this->record = $record;
        $this->form->operation('edit');

        $this->dispatch('panelist-record-saved', id: $key);

        Notification::make()
            ->title($isCreating
                ? __('filament-panels::resources/pages/create-record.notifications.created.title')
                : __('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->success()
            ->send();
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label(__('filament-actions::delete.single.label'))
            ->color('danger')
            ->size(Size::Small)
            ->visible(fn (): bool => $this->canDeleteRecord())
            ->requiresConfirmation()
            ->modalHeading(__('filament-actions::delete.single.modal.heading', ['label' => $this->resource::getModelLabel()]))
            ->modalSubmitActionLabel(__('filament-actions::delete.single.modal.actions.delete.label'))
            ->action(function (): void {
                abort_unless($this->canDeleteRecord(), 403);

                $id = $this->recordId;

                $this->callHook('beforeDelete');
                $this->getRecord()?->delete();
                $this->callHook('afterDelete');

                $this->resetEditor();

                $this->dispatch('panelist-record-deleted', id: $id);

                Notification::make()
                    ->title(__('filament-actions::delete.single.notifications.deleted.title'))
                    ->success()
                    ->send();
            });
    }

    public function canCreateRecord(): bool
    {
        return $this->resource::canCreate();
    }

    public function getNavigationIcon(): string | BackedEnum | Htmlable
    {
        return $this->resource::getNavigationIcon() ?? 'heroicon-o-rectangle-stack';
    }

    public function render(): View
    {
        return view('panelist::livewire.record-editor');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = new ($this->resource::getModel())($data);
        $record->save();

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);

        return $record;
    }

    /**
     * Resolve a record through the resource's route-binding query, which applies
     * getEloquentQuery() scoping such as tenancy and soft-delete visibility.
     */
    protected function resolveRecord(int | string $id): ?Model
    {
        return $this->resource::resolveRecordRouteBinding($id);
    }

    protected function getRecord(): ?Model
    {
        if ($this->record instanceof Model) {
            return $this->record;
        }

        if ($this->recordId === null) {
            return null;
        }

        return $this->record = $this->resolveRecord($this->recordId);
    }

    protected function canDeleteRecord(): bool
    {
        $record = $this->getRecord();

        return $record instanceof Model && $this->resource::canDelete($record);
    }

    /**
     * Call a lifecycle hook such as beforeSave() or afterCreate() when the editor defines it.
     */
    protected function callHook(string $hook): void
    {
        if (method_exists($this, $hook)) {
            $this->{$hook}();
        }
    }

    protected function resetEditor(): void
    {
        $this->recordId = null;
        $this->record = null;
        $this->isActive = false;

        $this->form
            ->model($this->resource::getModel())
            ->operation('create')
            ->fill();
    }
}
