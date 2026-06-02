@extends('layouts.app')

@section('content')
<div class="max-w-(--breakpoint-xl) mx-auto px-4 py-8">
    @auth
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                {{ session('success') }}
            </div>
        @endif
    @endauth

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Welcome to {{ \App\Helpers\SiteSettingsHelper::get('name') }}
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your account and access your dashboard.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @auth
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Submit a Ticket</h2>
                <x-validation-errors class="mb-4" />
                <form action="{{ route('tickets.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <x-label for="subject" value="Subject" />
                        <x-input id="subject" class="block mt-1 w-full" type="text" name="subject" required />
                        <x-input-error for="subject" class="mt-1" />
                    </div>
                    <div class="mb-4">
                        <x-label for="body" value="Description" />
                        <textarea id="body" name="body" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                        <x-input-error for="body" class="mt-1" />
                    </div>
                    <x-button class="bg-green-800 hover:bg-green-700 active:bg-green-900 focus:border-green-900 ring-green-300">
                        Submit Ticket
                    </x-button>
                </form>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Submit a Ticket</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Please
                    <a href="{{ Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/admin/login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                        log in
                    </a>
                    to submit a ticket.
                </p>
            </div>
        @endauth

        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Knowledge Base</h2>
            @if($knowledgeBaseArticles->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($knowledgeBaseArticles as $article)
                        <li>
                            <a href="{{ route('knowledge-base.show', $article) }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                {{ $article->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-sm">No articles yet.</p>
            @endif
        </div>
    </div>

    @auth
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Request a Quote</h2>
            <x-validation-errors class="mb-4" />
            <form action="{{ route('quote-requests.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-label for="name" value="Name" />
                        <x-input id="name" class="block mt-1 w-full" type="text" name="name" required />
                        <x-input-error for="name" class="mt-1" />
                    </div>
                    <div>
                        <x-label for="email" value="Email" />
                        <x-input id="email" class="block mt-1 w-full" type="email" name="email" required />
                        <x-input-error for="email" class="mt-1" />
                    </div>
                </div>
                <div class="mt-4">
                    <x-label for="message" value="Message" />
                    <textarea id="message" name="message" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                    <x-input-error for="message" class="mt-1" />
                </div>
                <div class="mt-4">
                    <x-button class="bg-green-800 hover:bg-green-700 active:bg-green-900 focus:border-green-900 ring-green-300">
                        Request Quote
                    </x-button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Request a Quote</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Please
                <a href="{{ Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/admin/login') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                    log in
                </a>
                to request a quote.
            </p>
        </div>
    @endauth
</div>
@endsection
