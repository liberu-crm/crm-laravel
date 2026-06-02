<div class="py-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Delete Account') }}</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Permanently delete your account.') }}</p>

    <div class="mt-6 max-w-xl text-sm text-gray-600 dark:text-gray-400">
        {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
    </div>

    <div class="mt-5">
        <x-danger-button class="bg-red-600 hover:bg-red-500 active:bg-red-700 focus:ring-red-500" wire:click="confirmUserDeletion" wire:loading.attr="disabled">
            {{ __('Delete Account') }}
        </x-danger-button>
    </div>

    <x-dialog-modal wire:model="confirmingUserDeletion">
        <x-slot name="title">
            {{ __('Delete Account') }}
        </x-slot>

        <x-slot name="content">
            <div class="text-gray-700 dark:text-gray-300">
                {{ __('Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </div>

            <div class="mt-4" x-data="{}" x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
                <x-input type="password" class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="{{ __('Password') }}"
                            x-ref="password"
                            wire:model="password"
                            wire:keydown.enter="deleteUser" />

                <x-input-error for="password" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button class="border-green-300 text-green-700 hover:bg-green-50 focus:ring-green-500" wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="bg-red-600 hover:bg-red-500 active:bg-red-700 focus:ring-red-500 ms-3" wire:click="deleteUser" wire:loading.attr="disabled">
                {{ __('Delete Account') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>
</div>
