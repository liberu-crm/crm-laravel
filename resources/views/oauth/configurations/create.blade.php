

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Connect New Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form method="POST" action="{{ route('oauth.configurations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <x-label for="service_name" value="{{ __('Service Type') }}" />
                        <select id="service_name" name="service_name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="whatsapp">WhatsApp Business</option>
                            <option value="facebook">Facebook Messenger</option>
                            <option value="gmail">Gmail / Google Workspace</option>
                            <option value="outlook">Outlook / Office 365</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <x-label for="account_name" value="{{ __('Account Name') }}" />
                        <x-input id="account_name" class="block mt-1 w-full" type="text" name="account_name" :value="old('account_name')" required placeholder="e.g. Sales WhatsApp, Support Facebook Page" />
                        <p class="mt-1 text-sm text-gray-500">Give this account a memorable name to identify it in your help desk</p>
                    </div>

                    <div id="facebook-settings" class="mb-4 hidden">
                        <x-label for="page_id" value="{{ __('Facebook Page ID') }}" />
                        <x-input id="page_id" class="block mt-1 w-full" type="text" name="additional_settings[page_id]" :value="old('additional_settings.page_id')" />
                    </div>

                    <div id="whatsapp-settings" class="mb-4 hidden">
                        <x-label for="phone_number" value="{{ __('WhatsApp Business Phone Number') }}" />
                        <x-input id="phone_number" class="block mt-1 w-full" type="text" name="additional_settings[phone_number]" :value="old('additional_settings.phone_number')" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Connect Account') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('service_name').addEventListener('change', function() {
            const service = this.value;
            document.getElementById('facebook-settings').classList.toggle('hidden', service !== 'facebook');
            document.getElementById('whatsapp-settings').classList.toggle('hidden', service !== 'whatsapp');
        });
    </script>
</x-app-layout>