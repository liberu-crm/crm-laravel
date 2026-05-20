<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <x-filament::button type="submit">
            Generate Report
        </x-filament::button>
    </form>

    @if($data)
        <div x-data="{ chart: null }" x-init="
            chart = new Chart($refs.canvas.getContext('2d'), {{ json_encode($data) }});
            $watch('$wire.data', value => {
                chart.data = value.data;
                chart.update();
            })
        ">
            <canvas x-ref="canvas"></canvas>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</x-filament-panels::page>