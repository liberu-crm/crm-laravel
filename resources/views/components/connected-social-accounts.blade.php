

<div class="mt-10 sm:mt-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Connected Social Media Accounts</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Manage your connected social media accounts and pages.
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 sm:p-6 bg-white shadow sm:rounded-lg">
                <div class="space-y-6">
                    @foreach(['facebook', 'linkedin', 'twitter', 'instagram', 'youtube'] as $provider)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <x-social-icon :provider="$provider" class="h-6 w-6" />
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    {{ ucfirst($provider) }}
                                </span>
                            </div>

                            @php
                                $account = $connectedAccounts->where('provider', $provider)->first();
                            @endphp

                            @if($account)
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-500 mr-3">
                                        Connected as {{ $account->name }}
                                    </span>
                                    <form method="POST" action="{{ route('oauth.disconnect', $provider) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">
                                            Disconnect
                                        </button>
                                    </form>
                                </div>
                            @else
                                <a href="{{ route('oauth.redirect', $provider) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    Connect
                                </a>
                            @endif
                        </div>

                        @if($account && !empty($account->metadata))
                            <div class="ml-9 mt-2">
                                @if($provider === 'facebook' && !empty($account->metadata['pages']))
                                    <div class="text-sm text-gray-600">
                                        <div class="font-medium">Connected Pages:</div>
                                        <ul class="list-disc ml-5">
                                            @foreach($account->metadata['pages'] as $page)
                                                <li>{{ $page['name'] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($provider === 'linkedin' && !empty($account->metadata['companies']))
                                    <div class="text-sm text-gray-600">
                                        <div class="font-medium">Connected Companies:</div>
                                        <ul class="list-disc ml-5">
                                            @foreach($account->metadata['companies'] as $company)
                                                <li>{{ $company['organizationName'] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($provider === 'youtube' && !empty($account->metadata['channels']))
                                    <div class="text-sm text-gray-600">
                                        <div class="font-medium">Connected Channels:</div>
                                        <ul class="list-disc ml-5">
                                            @foreach($account->metadata['channels'] as $channel)
                                                <li>{{ $channel->snippet->title }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>