<x-filament-panels::page>
    <form wire:submit="sendSMS">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Send SMS
        </x-filament::button>
    </form>

    <x-filament::button wire:click="makeCall" class="mt-4">
        Make Call
    </x-filament::button>
</x-filament-panels::page>