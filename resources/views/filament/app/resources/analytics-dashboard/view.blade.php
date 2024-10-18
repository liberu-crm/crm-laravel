<x-filament-panels::page>
    <x-filament::header>
        <x-slot name="heading">
            {{ __('Analytics Dashboard') }}
        </x-slot>
    </x-filament::header>

    <x-filament::section>
        <div>
            @if ($this->hasHeaderWidgets())
                <x-filament::widgets
                    :widgets="$this->getHeaderWidgets()"
                    :columns="$this->getHeaderWidgetsColumns()"
                    :data="$this->getWidgetsData()"
                />
            @endif

            {{-- Add your main dashboard content here --}}

            @if ($this->hasFooterWidgets())
                <x-filament::widgets
                    :widgets="$this->getFooterWidgets()"
                    :columns="$this->getFooterWidgetsColumns()"
                    :data="$this->getWidgetsData()"
                />
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
