

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add OAuth Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form method="POST" action="{{ route('oauth.configurations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <x-label for="service_name" value="{{ __('Service Name') }}" />
                        <select id="service_name" name="service_name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="gmail">Gmail</option>
                            <option value="office365">Office 365</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <x-label for="client_id" value="{{ __('Client ID') }}" />
                        <x-input id="client_id" class="block mt-1 w-full" type="text" name="client_id" :value="old('client_id')" required />
                    </div>

                    <div class="mb-4">
                        <x-label for="client_secret" value="{{ __('Client Secret') }}" />
                        <x-input id="client_secret" class="block mt-1 w-full" type="password" name="client_secret" required />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Create Configuration') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>