<x-filament-panels::page>
    @if (! $pipeline)
        <x-filament::callout icon="heroicon-o-information-circle" color="gray">
            <x-slot name="heading">
                No pipeline available
            </x-slot>
            Create a pipeline with stages to use the visual board. At least one active pipeline is preferred.
        </x-filament::callout>
    @else
        <div class="overflow-x-auto">
            <div class="flex gap-4 min-w-max pb-4">
                @foreach ($stages as $stage)
                    <div class="w-72 shrink-0 rounded-xl bg-gray-100 p-4 dark:bg-gray-800">
                        <h3 class="mb-4 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $stage->name }}
                        </h3>

                        <div
                            class="space-y-2 min-h-24"
                            data-stage-id="{{ $stage->id }}"
                            x-data
                            x-init="
                                new Sortable($el, {
                                    group: 'deals',
                                    animation: 150,
                                    onEnd(evt) {
                                        $wire.updateDealStage(
                                            parseInt(evt.item.dataset.dealId, 10),
                                            parseInt(evt.to.dataset.stageId, 10)
                                        );
                                    },
                                });
                            "
                        >
                            @foreach ($deals->get($stage->id, collect()) as $deal)
                                <div
                                    class="rounded-lg bg-white p-3 shadow-sm dark:bg-gray-900"
                                    data-deal-id="{{ $deal->id }}"
                                >
                                    <h4 class="font-medium text-gray-950 dark:text-white">
                                        {{ $deal->name }}
                                    </h4>
                                    @if ($deal->value)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            ${{ number_format((float) $deal->value, 2) }}
                                        </p>
                                    @endif
                                    @if ($deal->close_date)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                            {{ $deal->close_date->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endif
</x-filament-panels::page>
