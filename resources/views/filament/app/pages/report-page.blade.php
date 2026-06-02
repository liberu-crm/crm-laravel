<x-filament-panels::page>
    <div class="space-y-6">
        @if (! $mailchimpConfigured)
            <x-filament::callout icon="heroicon-o-exclamation-triangle" color="warning">
                <x-slot name="heading">
                    Mailchimp not configured
                </x-slot>
                Set <code>MAILCHIMP_API_KEY</code> and <code>MAILCHIMP_SERVER_PREFIX</code> in your
                <code>.env</code> file to generate A/B test and email campaign reports.
            </x-filament::callout>
        @endif

        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @if (! empty($data))
            @if (isset($data['type'], $data['data']))
                <x-filament::section heading="Report">
                    <div
                        x-data="{ chart: null }"
                        x-init="
                            chart = new Chart($refs.canvas.getContext('2d'), @js([
                                'type' => $data['type'],
                                'data' => $data['data'],
                            ]));
                            $watch('$wire.data', value => {
                                if (! value?.type || ! value?.data) {
                                    return;
                                }
                                chart.config.type = value.type;
                                chart.data = value.data;
                                chart.update();
                            });
                        "
                    >
                        <canvas x-ref="canvas" class="max-h-96"></canvas>
                    </div>
                </x-filament::section>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            @else
                <x-filament::section heading="Report">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <tbody>
                                @foreach ($data as $key => $value)
                                    <tr>
                                        <td class="p-2 font-medium">{{ str($key)->headline() }}</td>
                                        <td class="p-2">{{ is_scalar($value) ? $value : json_encode($value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
