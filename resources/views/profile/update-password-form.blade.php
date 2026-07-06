<div class="py-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Update Password') }}</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div class="max-w-lg">
            <x-label for="current_password" value="{{ __('Current Password') }}" />
            <x-input id="current_password" type="password" class="mt-1 block w-full" wire:model="state.current_password" autocomplete="current-password" />
            <x-input-error for="current_password" class="mt-2" />
        </div>

        <div class="max-w-lg">
            <x-label for="password" value="{{ __('New Password') }}" />
            <x-input id="password" type="password" class="mt-1 block w-full" wire:model="state.password" autocomplete="new-password" />
            <x-input-error for="password" class="mt-2" />
        </div>

        <div class="max-w-lg">
            <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
            <x-input id="password_confirmation" type="password" class="mt-1 block w-full" wire:model="state.password_confirmation" autocomplete="new-password" />
            <x-input-error for="password_confirmation" class="mt-2" />
        </div>

        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
            <x-action-message class="me-3" on="saved">
                {{ __('Saved.') }}
            </x-action-message>
            <x-button class="bg-green-800 hover:bg-green-700 active:bg-green-900 focus:border-green-900 ring-green-300">
                {{ __('Save') }}
            </x-button>
        </div>
    </form>
</div>
