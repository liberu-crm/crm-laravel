<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \App\Helpers\SiteSettingsHelper::get('name') }}</title>

    @if(config('googletagmanager.id'))
        @include('googletagmanager::head')
    @endif

    <!-- Styles -->
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="font-sans antialiased">
    @if(config('googletagmanager.id'))
        @include('googletagmanager::body')
    @endif

    <div class="min-h-screen bg-gray-100 flex flex-col">
        @include('components.home-navbar')

        <main class="flex-grow">
            @yield('content')
        </main>

        @include('components.footer')
    </div>

    <!-- Notification Bell -->
    <div class="fixed top-4 right-4">
        <div class="dropdown relative">
            <button class="dropdown-toggle text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="badge bg-red-500 text-white text-xs rounded-full px-1 absolute -top-1 -right-1">
                    {{ auth()->user()->unreadNotifications->count() }}
                </span>
            </button>
            <div class="dropdown-menu hidden absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-20">
                <div class="py-2">
                    <h3 class="text-lg font-semibold px-4 py-2 border-b">Notifications</h3>
                    <div class="max-h-64 overflow-y-auto">
                        @forelse(auth()->user()->inAppNotifications()->take(5)->get() as $notification)
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $notification->read_at ? 'opacity-50' : '' }}">
                                {{ $notification->data['event'] }}
                            </a>
                        @empty
                            <p class="px-4 py-2 text-sm text-gray-500">No new notifications</p>
                        @endforelse
                    </div>
                    <div class="border-t px-4 py-2">
                        <a href="{{ route('notifications.index') }}" class="text-sm text-blue-500 hover:underline">View all notifications</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    @vite('resources/js/app.js')
    @livewireScripts

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');

                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    menu.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target)) {
                        menu.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>
