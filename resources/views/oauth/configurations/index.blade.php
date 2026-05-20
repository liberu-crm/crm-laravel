<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Connected Social Accounts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex justify-end mb-4">
                    <a href="{{ route('oauth.configurations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Connect New Account
                    </a>
                </div>

                @if ($configurations->isEmpty())
                    <p class="text-gray-500 text-center py-8">No social accounts connected yet. Click "Connect New Account" to get started.</p>
                @else
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b-2 border-gray-300 text-left text-sm leading-4 tracking-wider">Platform</th>
                                <th class="px-6 py-3 border-b-2 border-gray-300 text-left text-sm leading-4 tracking-wider">Account Name</th>
                                <th class="px-6 py-3 border-b-2 border-gray-300 text-left text-sm leading-4 tracking-wider">Status</th>
                                <th class="px-6 py-3 border-b-2 border-gray-300 text-left text-sm leading-4 tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($configurations as $config)
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-500 capitalize">{{ $config->service_name }}</td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-500">{{ $config->account_name ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-500">
                                        @if ($config->is_active)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Connected</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Not Connected</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-500 space-x-3">
                                        <a href="{{ route('oauth.authenticate', ['service' => $config->service_name, 'configId' => $config->id]) }}"
                                           class="text-blue-600 hover:text-blue-900">
                                            {{ $config->is_active ? 'Reconnect' : 'Connect' }}
                                        </a>
                                        <form method="POST" action="{{ route('oauth.configurations.destroy', $config) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Remove this account?')">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
