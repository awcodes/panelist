<div class="panelist-record-editor h-full">
    @php
        $keyboardShortcuts = \Awcodes\Panelist\PanelistPlugin::get()->hasKeyboardShortcuts();
        $isEditing = $this->recordId !== null;
        $isCreating = $this->isActive && $this->recordId === null;
    @endphp

    <div
        class="flex h-full flex-col"
        x-data="{}"
        @panelist-record-selected.window="$wire.loadRecord($event.detail.id)"
        @panelist-create-record.window="$wire.createRecord()"
        @if ($keyboardShortcuts)
        @keydown.meta.s.window.prevent="$wire.save()"
        @keydown.ctrl.s.window.prevent="$wire.save()"
        @keydown.escape.window="$wire.cancel()"
        @endif
        @panelist-record-saved.window="$wire.$refresh()"
    >
        @if ($this->isActive)
            {{-- Header --}}
            <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-950/20 pl-4 py-3 dark:border-white/10">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    @if ($isEditing)
                        {{ __('filament-panels::resources/pages/edit-record.breadcrumb') }}
                    @else
                        {{ __('filament-panels::resources/pages/create-record.breadcrumb') }}
                    @endif
                </span>

                <div class="flex items-center gap-x-2">
                    <x-filament::button
                        wire:click="cancel"
                        size="sm"
                        color="gray"
                    >
                        @if ($isEditing)
                            {{ __('filament-panels::resources/pages/edit-record.form.actions.cancel.label') }}
                        @else
                            {{ __('filament-panels::resources/pages/create-record.form.actions.cancel.label') }}
                        @endif
                    </x-filament::button>

                    @if ($isEditing)
                        {{ $this->deleteAction }}
                    @endif

                    <x-filament::button
                        wire:click="save"
                        size="sm"
                    >
                        @if ($isEditing)
                            {{ __('filament-panels::resources/pages/edit-record.form.actions.save.label') }}
                        @else
                            {{ __('filament-panels::resources/pages/create-record.form.actions.create.label') }}
                        @endif
                    </x-filament::button>
                </div>
            </div>

            {{-- Form --}}
            <div class="flex-1 overflow-auto p-6">
                {{ $this->form }}
            </div>
        @else
            {{-- Empty state --}}
            <div class="flex h-full flex-col items-center justify-center gap-y-3 text-gray-400 dark:text-gray-500">
                <x-filament::icon
                    :icon="$this->getNavigationIcon()"
                    class="h-12 w-12 text-gray-300 dark:text-gray-600"
                />
                <p class="text-sm">
                    {{ __('panelist::panelist.select_or_create') }}
                </p>
                <x-filament::button
                    wire:click="$dispatch('panelist-create-record')"
                    size="sm"
                    color="gray"
                >
                    {{ __('filament-panels::resources/pages/create-record.breadcrumb') }}
                </x-filament::button>
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</div>
