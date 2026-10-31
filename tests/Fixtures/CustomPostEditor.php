<?php

declare(strict_types=1);

namespace Awcodes\Panelist\Tests\Fixtures;

use Awcodes\Panelist\Livewire\RecordEditor;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Str;

class CustomPostEditor extends RecordEditor
{
    /**
     * Hooks called on this instance, in order.
     *
     * @var array<string>
     */
    public array $calledHooks = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['title'] = Str::upper((string) $data['title']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['title'] = Str::title((string) $data['title']);

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['content'] = 'Created by a custom editor.';

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->calledHooks[] = 'beforeSave';

        if ($this->data['title'] === 'halt') {
            throw new Halt;
        }
    }

    protected function afterSave(): void
    {
        $this->calledHooks[] = 'afterSave';
    }

    protected function beforeCreate(): void
    {
        $this->calledHooks[] = 'beforeCreate';
    }

    protected function afterCreate(): void
    {
        $this->calledHooks[] = 'afterCreate';
    }

    protected function afterDelete(): void
    {
        $this->calledHooks[] = 'afterDelete';
    }
}
