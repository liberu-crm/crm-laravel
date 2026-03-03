<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Connect New Account') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <p class="mb-6 text-gray-600">
                    Connect your social media accounts to allow the CRM to post content on your behalf.
                    You will be redirected to each platform to approve the necessary permissions.
                </p>

                <form method="POST" action="{{ route('oauth.configurations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <x-label for="service_name" value="{{ __('Platform') }}" />
                        <select id="service_name" name="service_name" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <optgroup label="Social Media (Posting)">
                                <option value="facebook">Facebook (Pages &amp; Profiles)</option>
                                <option value="twitter">Twitter / X</option>
                                <option value="instagram">Instagram (Business Accounts)</option>
                                <option value="linkedin">LinkedIn (Profiles &amp; Pages)</option>
                                <option value="youtube">YouTube (via Google Account)</option>
                            </optgroup>
                            <optgroup label="Messaging &amp; Email">
                                <option value="whatsapp">WhatsApp Business</option>
                                <option value="gmail">Gmail / Google Workspace</option>
                                <option value="outlook">Outlook / Office 365</option>
                            </optgroup>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Select the platform you want to connect</p>
                    </div>

                    <div class="mb-4">
                        <x-label for="account_name" value="{{ __('Account Name') }}" />
                        <x-input id="account_name" class="block mt-1 w-full" type="text" name="account_name" :value="old('account_name')" required placeholder="e.g. Company Facebook Page, Personal Twitter" />
                        <p class="mt-1 text-sm text-gray-500">Give this account a memorable name to identify it</p>
                    </div>

                    {{-- Platform-specific help text --}}
                    <div id="platform-help" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded hidden">
                        <div id="help-facebook" class="hidden">
                            <h4 class="font-semibold text-blue-800">Facebook</h4>
                            <p class="text-sm text-blue-700 mt-1">You will be asked to grant permission to manage your Facebook Pages and Instagram Business accounts linked to them. Posts, images, and videos can then be published to those pages.</p>
                        </div>
                        <div id="help-twitter" class="hidden">
                            <h4 class="font-semibold text-blue-800">Twitter / X</h4>
                            <p class="text-sm text-blue-700 mt-1">You will be asked to grant permission to post tweets, reply, and upload images and videos to your Twitter/X account.</p>
                        </div>
                        <div id="help-instagram" class="hidden">
                            <h4 class="font-semibold text-blue-800">Instagram</h4>
                            <p class="text-sm text-blue-700 mt-1">Instagram posting requires a Professional (Business or Creator) account linked to a Facebook Page. You will authorise via Facebook to publish images and videos to your Instagram account.</p>
                        </div>
                        <div id="help-linkedin" class="hidden">
                            <h4 class="font-semibold text-blue-800">LinkedIn</h4>
                            <p class="text-sm text-blue-700 mt-1">You will be asked to grant permission to post on your LinkedIn profile and any Company Pages you manage. Text posts, images, and videos are supported.</p>
                        </div>
                        <div id="help-youtube" class="hidden">
                            <h4 class="font-semibold text-blue-800">YouTube</h4>
                            <p class="text-sm text-blue-700 mt-1">You will authorise via your Google account to upload videos to your YouTube channel.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4 space-x-4">
                        <a href="{{ route('oauth.configurations.index') }}" class="text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-button>
                            {{ __('Connect Account') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const platformHelp = document.getElementById('platform-help');
        const helpDivs = {
            facebook: document.getElementById('help-facebook'),
            twitter: document.getElementById('help-twitter'),
            instagram: document.getElementById('help-instagram'),
            linkedin: document.getElementById('help-linkedin'),
            youtube: document.getElementById('help-youtube'),
        };

        document.getElementById('service_name').addEventListener('change', function () {
            const service = this.value;

            // Hide all help divs
            Object.values(helpDivs).forEach(el => el && el.classList.add('hidden'));

            if (helpDivs[service]) {
                helpDivs[service].classList.remove('hidden');
                platformHelp.classList.remove('hidden');
            } else {
                platformHelp.classList.add('hidden');
            }
        });

        // Trigger on load to show help for the default selected option
        document.getElementById('service_name').dispatchEvent(new Event('change'));
    </script>
</x-app-layout>
