<x-filament-panels::page>
    @php
        $resource = static::getResource();
        $plugin = \Awcodes\Panelist\PanelistPlugin::get();
        $sidebarWidth = $plugin->getSidebarWidth();
        $keyboardShortcuts = $plugin->hasKeyboardShortcuts();
    @endphp

    <div
        class="panelist-layout flex h-full overflow-hidden"
        x-data="{
            sidebarWidth: parseInt(localStorage.getItem('panelist.sidebar_width') ?? {{ $sidebarWidth }}),
            isDragging: false,
            startX: 0,
            startWidth: 0,
            focusedKey: null,
            canCreate: @js($resource::canCreate()),
            keyboardShortcuts: @js($keyboardShortcuts),
            stopRepaintingFocus: null,
            init() {
                if (!this.keyboardShortcuts) return;

                // Rows are re-rendered after every table update, and Alpine re-applies their bound classes after the morph, so the focus ring is repainted on the next frame.
                this.stopRepaintingFocus = Livewire.hook('morphed', () => requestAnimationFrame(() => this.paintFocus()));
            },
            destroy() {
                this.stopRepaintingFocus?.();
            },
            startDrag(event) {
                this.isDragging = true;
                this.startX = event.clientX;
                this.startWidth = this.sidebarWidth;
            },
            onDrag(event) {
                if (!this.isDragging) return;
                const delta = event.clientX - this.startX;
                this.sidebarWidth = Math.max(200, Math.min(600, this.startWidth + delta));
            },
            stopDrag() {
                if (!this.isDragging) return;
                this.isDragging = false;
                localStorage.setItem('panelist.sidebar_width', this.sidebarWidth);
            },
            component(ref) {
                const el = this.$refs[ref]?.querySelector('[wire\\:id]');

                return el ? Livewire.find(el.getAttribute('wire:id')) : null;
            },
            rows() {
                return Array.from(this.$refs.sidebar.querySelectorAll('[wire\\:key*=\'.table.records.\']'));
            },
            rowKey(row) {
                const wireKey = row.getAttribute('wire:key');

                return wireKey.slice(wireKey.indexOf('.table.records.') + '.table.records.'.length);
            },
            isTyping(event) {
                const target = event.target;

                return target instanceof HTMLElement
                    && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
            },
            hasOpenModal() {
                return document.querySelector('.fi-modal-open') !== null;
            },
            moveFocus(step) {
                const rows = this.rows();

                if (rows.length === 0) return;

                const keys = rows.map((row) => this.rowKey(row));
                const selected = rows.find((row) => row.classList.contains('panelist-selected-record'));
                const start = keys.indexOf(this.focusedKey ?? (selected ? this.rowKey(selected) : null));
                const index = start === -1
                    ? (step > 0 ? 0 : rows.length - 1)
                    : Math.max(0, Math.min(rows.length - 1, start + step));

                this.focusedKey = keys[index];
                this.paintFocus();
                rows[index].scrollIntoView({ block: 'nearest' });
            },
            paintFocus() {
                this.rows().forEach((row) => {
                    row.classList.toggle('panelist-focused-record', this.rowKey(row) === this.focusedKey);
                });
            },
            openFocused() {
                if (this.focusedKey === null) return;
                this.component('sidebar')?.$call('selectRecord', this.focusedKey);
            },
            onKeydown(event) {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                    if (this.hasOpenModal()) return;
                    event.preventDefault();
                    this.component('editor')?.$call('save');

                    return;
                }

                if (event.metaKey || event.ctrlKey || event.altKey || this.isTyping(event) || this.hasOpenModal()) return;

                switch (event.key) {
                    case 'ArrowDown':
                    case 'j':
                        event.preventDefault();
                        this.moveFocus(1);
                        break;
                    case 'ArrowUp':
                    case 'k':
                        event.preventDefault();
                        this.moveFocus(-1);
                        break;
                    case 'Enter':
                    case 'o':
                        // Leave Enter to a focused button or link.
                        if (this.focusedKey === null || event.target.closest?.('a, button, [role=button], [role=option], [role=menuitem]')) return;
                        event.preventDefault();
                        this.openFocused();
                        break;
                    case 'c':
                        if (!this.canCreate) return;
                        event.preventDefault();
                        Livewire.dispatch('panelist-create-record');
                        break;
                    case 'Escape':
                        this.component('editor')?.$call('cancel');
                        break;
                }
            },
        }"
        x-on:mousemove="onDrag"
        x-on:mouseup="stopDrag"
        x-on:mouseleave="stopDrag"
        @if ($keyboardShortcuts)
            x-on:keydown.window="onKeydown($event)"
        @endif
    >
        {{-- Sidebar Panel --}}
        <div
            x-ref="sidebar"
            class="panelist-sidebar flex-shrink-0 overflow-auto"
            :style="`width: ${sidebarWidth}px; min-width: ${sidebarWidth}px;`"
        >
            @livewire(
                'panelist.sidebar-table',
                ['resource' => $resource, 'selectedRecordId' => $this->activeRecordId ?: null],
                key('panelist-sidebar-' . $resource)
            )
        </div>

        {{-- Resize Handle --}}
        <div
            class="panelist-resize-handle flex w-1 flex-shrink-0 cursor-col-resize items-center justify-center bg-gray-950/10 hover:bg-primary-500 active:bg-primary-600 dark:bg-white/10 dark:hover:bg-primary-500"
            x-on:mousedown.prevent="startDrag"
            :class="{ 'bg-primary-500': isDragging }"
        >
        </div>

        {{-- Editor Panel --}}
        <div x-ref="editor" class="panelist-editor min-w-0 flex-1 overflow-auto">
            @livewire(
                $this->getEditor(),
                ['resource' => $resource, 'initialRecordId' => $this->activeRecordId ?: null],
                key('panelist-editor-' . $resource)
            )
        </div>
    </div>
</x-filament-panels::page>
