

<div class="p-4">
    <div class="text-lg font-bold mb-4">Engagement Metrics</div>
    
    @if($getRecord() && $getRecord()->status === \App\Models\SocialMediaPost::STATUS_PUBLISHED)
        <div class="space-y-4">
            <!-- Engagement Overview -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-lg p-4 shadow">
                    <div class="text-sm text-gray-500">Likes</div>
                    <div class="text-2xl font-bold">{{ number_format($getRecord()->likes) }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow">
                    <div class="text-sm text-gray-500">Shares</div>
                    <div class="text-2xl font-bold">{{ number_format($getRecord()->shares) }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow">
                    <div class="text-sm text-gray-500">Comments</div>
                    <div class="text-2xl font-bold">{{ number_format($getRecord()->comments) }}</div>
                </div>
            </div>

            <!-- Platform Breakdown -->
            <div class="bg-white rounded-lg p-4 shadow">
                <div class="text-sm font-semibold mb-3">Platform Breakdown</div>
                @foreach($getRecord()->platforms as $platform)
                    <div class="mb-2">
                        <div class="flex justify-between text-sm">
                            <span>{{ ucfirst($platform) }}</span>
                            <span class="font-medium">
                                {{ number_format($getRecord()->platform_metrics[$platform]['engagement_rate'] ?? 0) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div 
                                class="bg-primary-600 h-2 rounded-full" 
                                style="width: {{ $getRecord()->platform_metrics[$platform]['engagement_rate'] ?? 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Engagement Over Time -->
            <div class="bg-white rounded-lg p-4 shadow">
                <div class="text-sm font-semibold mb-3">Engagement Over Time</div>
                <div class="h-48">
                    <canvas id="engagementChart"></canvas>
                </div>
            </div>
        </div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('engagementChart').getContext('2d');
            const data = @json($getRecord()->getEngagementTimelineData());
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Likes',
                            data: data.likes,
                            borderColor: '#4F46E5',
                            tension: 0.1
                        },
                        {
                            label: 'Shares',
                            data: data.shares,
                            borderColor: '#10B981',
                            tension: 0.1
                        },
                        {
                            label: 'Comments',
                            data: data.comments,
                            borderColor: '#F59E0B',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
        @endpush
    @else
        <div class="text-gray-500 text-sm">
            Engagement metrics will be available once the post is published.
        </div>
    @endif
</div>