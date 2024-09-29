@props(['breadcrumbs'])

<nav aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2 text-sm text-gray-500">
        @foreach ($breadcrumbs as $breadcrumb)
            <li>
                @if (!$loop->last)
                    <a href="{{ $breadcrumb['url'] }}" class="hover:text-gray-700">
                        {{ $breadcrumb['label'] }}
                    </a>
                    <span class="mx-2">/</span>
                @else
                    <span class="text-gray-900">{{ $breadcrumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>