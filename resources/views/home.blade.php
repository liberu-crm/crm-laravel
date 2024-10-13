@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-8">Welcome to {{ config('app.name') }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <h2 class="text-2xl font-semibold mb-4">Submit a Ticket</h2>
            <form action="{{ route('tickets.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="subject" class="block mb-2">Subject</label>
                    <input type="text" name="subject" id="subject" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label for="body" class="block mb-2">Description</label>
                    <textarea name="body" id="body" rows="4" class="w-full border rounded px-3 py-2" required></textarea>
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Submit Ticket</button>
            </form>
        </div>

        <div>
            <h2 class="text-2xl font-semibold mb-4">Knowledge Base</h2>
            <ul class="list-disc pl-5">
                @foreach($knowledgeBaseArticles as $article)
                    <li class="mb-2">
                        <a href="{{ route('knowledge-base.show', $article) }}" class="text-blue-500 hover:underline">
                            {{ $article->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-12">
        <h2 class="text-2xl font-semibold mb-4">Request a Quote</h2>
        <form action="{{ route('quote-requests.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="name" class="block mb-2">Name</label>
                <input type="text" name="name" id="name" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block mb-2">Email</label>
                <input type="email" name="email" id="email" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-4">
                <label for="message" class="block mb-2">Message</label>
                <textarea name="message" id="message" rows="4" class="w-full border rounded px-3 py-2" required></textarea>
            </div>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Request Quote</button>
        </form>
    </div>
</div>
@endsection