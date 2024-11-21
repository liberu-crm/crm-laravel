

<div class="p-4 space-y-4">
    <div class="text-lg font-bold">Post Preview</div>
    
    @foreach($getRecord()->platforms as $platform)
        <div class="border rounded-lg p-4 mb-4 bg-white shadow">
            <div class="flex items-center mb-3">
                <x-dynamic-component 
                    :component="'heroicon-o-' . $platform" 
                    class="w-5 h-5 mr-2"
                />
                <span class="font-semibold">{{ ucfirst($platform) }} Preview</span>
            </div>
            
            <div class="space-y-3">
                <div class="text-sm">
                    {{ $getRecord()->content }}
                </div>
                
                @if($getRecord()->image)
                    <div class="mt-2">
                        <img 
                            src="{{ Storage::url($getRecord()->image) }}" 
                            alt="Post image"
                            class="rounded-lg max-h-48 object-cover"
                        >
                    </div>
                @endif
                
                @if($getRecord()->link)
                    <div class="text-blue-600 text-sm break-all">
                        {{ $getRecord()->link }}
                    </div>
                @endif
                
                <div class="text-xs text-gray-500">
                    Scheduled for: {{ $getRecord()->scheduled_at->format('M d, Y H:i') }}
                </div>
            </div>
        </div>
    @endforeach
</div>