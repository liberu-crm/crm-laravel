<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="submit">
            {{ $this->form }}

            <x-filament::button type="submit" class="mt-4">
                Generate Report
            </x-filament::button>
        </form>
    </x-filament::section>

    @if ($leadQualityReport)
        <x-filament::section heading="Lead Quality Report" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left p-2">Metric</th>
                            <th class="text-left p-2">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leadQualityReport as $key => $value)
                            <tr>
                                <td class="p-2">{{ $key }}</td>
                                <td class="p-2">{{ $value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif

    @if ($leadScoreDistribution)
        <x-filament::section heading="Lead Score Distribution" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left p-2">Score Range</th>
                            <th class="text-left p-2">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leadScoreDistribution as $range => $count)
                            <tr>
                                <td class="p-2">{{ $range }}</td>
                                <td class="p-2">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
