<x-filament-panels::page>
    <div class="space-y-6">
        @if(! $isConfigured)
            <x-filament::callout icon="heroicon-o-exclamation-triangle" color="warning">
                <x-slot name="heading">
                    Mailchimp not configured
                </x-slot>
                Please set <code>MAILCHIMP_API_KEY</code> and <code>MAILCHIMP_SERVER_PREFIX</code>
                in your <code>.env</code> file to enable Mailchimp integration features.
            </x-filament::callout>
        @else
            <div class="flex gap-4">
                {{ $createListAction }}
                {{ $createCampaignAction }}
            </div>

            @if(count($listData))
                <x-filament::section>
                    <x-slot name="heading">
                        Lists
                    </x-slot>

                    <ul class="list-disc list-inside">
                        @foreach($listData as $id => $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if(count($campaignData))
                <x-filament::section>
                    <x-slot name="heading">
                        Campaigns
                    </x-slot>

                    @foreach($campaignData as $campaign)
                        <div class="py-1">{{ $campaign['name'] }} — {{ $campaign['status'] }}</div>
                    @endforeach
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
