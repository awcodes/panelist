<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Pages;

use Awcodes\Panelist\Livewire\RecordEditor;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

abstract class PanelistPage extends Page
{
    /**
     * The currently selected record ID, synced with the URL query string.
     */
    #[Url(as: 'record', except: '')]
    public string $activeRecordId = '';

    /**
     * The Livewire component that edits records. Extend RecordEditor to customize it.
     *
     * @var class-string<RecordEditor>
     */
    protected static string $editor = RecordEditor::class;

    protected string $view = 'panelist::pages.panelist';

    /**
     * @return class-string<RecordEditor>
     */
    public static function getEditor(): string
    {
        return static::$editor;
    }

    #[On('panelist-record-selected')]
    public function syncActiveRecord(int | string $id): void
    {
        $this->activeRecordId = (string) $id;
        $this->skipRender();
    }

    #[On('panelist-record-saved')]
    public function syncSavedRecord(int | string $id): void
    {
        $this->activeRecordId = (string) $id;
        $this->skipRender();
    }

    #[On('panelist-record-cancelled')]
    #[On('panelist-record-deleted')]
    public function clearActiveRecord(): void
    {
        $this->activeRecordId = '';
        $this->skipRender();
    }

    public function getTitle(): string
    {
        return __('panelist::panelist.manage', ['label' => static::getResource()::getPluralModelLabel()]);
    }

    public function getBreadcrumb(): ?string
    {
        return null;
    }

    public function hasResourceBreadcrumbs(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('filament-actions::create.single.label', ['label' => static::getResource()::getModelLabel()]))
                ->visible(fn (): bool => static::getResource()::canCreate())
                ->action(fn () => $this->dispatch('panelist-create-record')),
        ];
    }
}
