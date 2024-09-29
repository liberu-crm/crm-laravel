<div>
    <div class="p-6">
        <div class="flex justify-end mb-4">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none transition duration-150 ease-in-out">
                        <div>Add Widget</div>
                        <div class="ml-1">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link wire:click="addWidget('stats')">
                        {{ __('Stats Widget') }}
                    </x-dropdown-link>
                    <x-dropdown-link wire:click="addWidget('tasks')">
                        {{ __('Tasks Widget') }}
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>

        <div wire:sortable="updateWidgetOrder" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($widgets as $widget)
                <div wire:key="widget-{{ $widget->id }}" wire:sortable.item="{{ $widget->id }}" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">

                            <h3 class="text-lg font-medium text-gray-900">{{ ucfirst($widget->widget_type) }} Widget</h3>
                            <button wire:click="removeWidget({{ $widget->id }})" class="text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        @if ($widget->widget_type === 'stats')
                            @livewire('widgets.stats', ['widget' => $widget], key('stats-'.$widget->id))
                        @elseif ($widget->widget_type === 'tasks')
                            @livewire('widgets.tasks', ['widget' => $widget], key('tasks-'.$widget->id))
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/gh/livewire/sortable@v0.x.x/dist/livewire-sortable.js"></script>
@endpush