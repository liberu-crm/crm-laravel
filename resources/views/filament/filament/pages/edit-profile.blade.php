<x-filament-panels::page>
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            @livewire(Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm::class)
        @endif

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            @livewire(Laravel\Jetstream\Http\Livewire\UpdatePasswordForm::class)
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            @livewire(Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm::class)
        @endif

        @livewire(Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm::class)

        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
            @livewire(Laravel\Jetstream\Http\Livewire\DeleteUserForm::class)
        @endif
    </div>
</x-filament-panels::page>
