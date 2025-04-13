<x-filament::page>
    <div class="grid grid-cols-1 gap-4">
        @foreach ($this->getHeaderWidgets() as $widget)
            {{ $widget }}
        @endforeach
    </div>
</x-filament::page>
