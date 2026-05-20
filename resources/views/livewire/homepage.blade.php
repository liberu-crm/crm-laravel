<x-guest-layout>
    <section>
        @foreach ($contents as $element)
            {!! $element['content'] !!}
        @endforeach
    </section>
    <section class="featured-posts">
        @if ($featuredPosts->isEmpty())
            <section class="py-8 flex justify-center">
                <p>No featured posts yet.</p>
            </section>
        @endif
        @foreach ($featuredPosts as $post)
            <article class="post">
                <h2>{{ $post->title }}</h2>
                <p>{{ $post->summary }}</p>
                <a href="{{ route('posts.show', $post->id) }}">Read More</a>
            </article>
        @endforeach
    </section>
</x-guest-layout>