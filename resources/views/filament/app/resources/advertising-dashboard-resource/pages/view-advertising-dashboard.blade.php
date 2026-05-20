<x-filament-panels::page>
    {{-- Header Widgets --}}
    @if ($this->getHeaderWidgets())
        <x-filament::section>
            <x-filament::grid
                :default="$this->getHeaderWidgetsColumns()"
                :sm="$this->getHeaderWidgetsColumns('sm')"
                :md="$this->getHeaderWidgetsColumns('md')"
                :lg="$this->getHeaderWidgetsColumns('lg')"
                :xl="$this->getHeaderWidgetsColumns('xl')"
                :two-xl="$this->getHeaderWidgetsColumns('2xl')"
                class="mb-6 gap-6"
            >
                @foreach ($this->getHeaderWidgets() as $widget)
                    {{ $widget }}
                @endforeach
            </x-filament::grid>
        </x-filament::section>
    @endif

    {{-- Main Dashboard Content --}}
    <x-filament::section>
        <div class="space-y-6">
            <h2 class="text-2xl font-bold tracking-tight">Advertising Dashboard</h2>
            <p>Welcome to your Advertising Dashboard. Here you can view and manage your advertising campaigns and performance metrics.</p>
            {{-- Add more dashboard content here --}}
        </div>
    </x-filament::section>

    {{-- Footer Widgets --}}
    @if ($this->getFooterWidgets())
        <x-filament::section>
            <x-filament::grid
                :default="$this->getFooterWidgetsColumns()"
                :sm="$this->getFooterWidgetsColumns('sm')"
                :md="$this->getFooterWidgetsColumns('md')"
                :lg="$this->getFooterWidgetsColumns('lg')"
                :xl="$this->getFooterWidgetsColumns('xl')"
                :two-xl="$this->getFooterWidgetsColumns('2xl')"
                class="mt-6 gap-6"
            >
                @foreach ($this->getFooterWidgets() as $widget)
                    {{ $widget }}
                @endforeach
            </x-filament::grid>
        </x-filament::section>
    @endif
</x-filament-panels::page>
