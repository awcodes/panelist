<?php

declare(strict_types=1);

namespace Awcodes\Panelist;

use Awcodes\Panelist\Livewire\RecordEditor;
use Awcodes\Panelist\Pages\PanelistPage;
use Filament\Facades\Filament;
use InvalidArgumentException;
use Livewire\Livewire;

class Panelist
{
    /**
     * Register the editor component of every Panelist page in every panel.
     *
     * A custom editor is mounted by class name, and Livewire can only resolve that
     * class again on later update requests if it was registered on this request.
     */
    public function registerEditorComponents(): void
    {
        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getResources() as $resource) {
                foreach ($resource::getPages() as $registration) {
                    $page = $registration->getPage();

                    if (is_subclass_of($page, PanelistPage::class)) {
                        $this->registerEditorComponent($page);
                    }
                }
            }
        }
    }

    /**
     * @param  class-string<PanelistPage>  $page
     */
    public function registerEditorComponent(string $page): void
    {
        $editor = $page::getEditor();

        if ($editor === RecordEditor::class) {
            return;
        }

        // The class-string<RecordEditor> type is only a docblock; a page can still declare any class.
        if (! is_subclass_of($editor, RecordEditor::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new InvalidArgumentException("The editor [{$editor}] of [{$page}] must extend [" . RecordEditor::class . '].');
        }

        Livewire::component($editor);
    }
}
