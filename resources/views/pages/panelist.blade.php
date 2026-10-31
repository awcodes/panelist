<x-filament-panels::page>
    @php
        $resource = static::getResource();
        $sidebarWidth = \Awcodes\Panelist\PanelistPlugin::get()->getSidebarWidth();
    @endphp

    <div
        class="panelist-layout flex h-full overflow-hidden"
        x-data="{
            sidebarWidth: parseInt(localStorage.getItem('panelist.sidebar_width') ?? {{ $sidebarWidth }}),
            isDragging: false,
            startX: 0,
            startWidth: 0,
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
        }"
        @mousemove="onDrag"
        @mouseup="stopDrag"
        @mouseleave="stopDrag"
    >
        {{-- Sidebar Panel --}}
        <div
            class="panelist-sidebar flex-shrink-0 overflow-auto"
            :style="`width: ${sidebarWidth}px; min-width: ${sidebarWidth}px;`"
        >
            @livewire('panelist.sidebar-table', ['resource' => $resource], key('panelist-sidebar-' . $resource))
        </div>

        {{-- Resize Handle --}}
        <div
            class="panelist-resize-handle flex w-1 flex-shrink-0 cursor-col-resize items-center justify-center bg-gray-950/10 hover:bg-primary-500 active:bg-primary-600 dark:bg-white/10 dark:hover:bg-primary-500"
            @mousedown.prevent="startDrag"
            :class="{ 'bg-primary-500': isDragging }"
        >
        </div>

        {{-- Editor Panel --}}
        <div class="panelist-editor min-w-0 flex-1 overflow-auto">
            @livewire(
                $this->getEditor(),
                ['resource' => $resource, 'initialRecordId' => $this->activeRecordId ?: null],
                key('panelist-editor-' . $resource)
            )
        </div>
    </div>
</x-filament-panels::page>
