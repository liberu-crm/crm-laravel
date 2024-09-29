<div>
    <div class="mb-4">
        <label for="reportType" class="block text-sm font-medium text-gray-700">Report Type</label>
        <select id="reportType" wire:model="reportType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            <option value="contact-interactions">Contact Interactions</option>
            <option value="sales-pipeline">Sales Pipeline</option>
            <option value="customer-engagement">Customer Engagement</option>
        </select>
    </div>

    <div class="mb-4">
        <label for="dateRange" class="block text-sm font-medium text-gray-700">Date Range</label>
        <select id="dateRange" wire:model="dateRange" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            <option value="last-7-days">Last 7 Days</option>
            <option value="last-30-days">Last 30 Days</option>
            <option value="last-90-days">Last 90 Days</option>
            <option value="year-to-date">Year to Date</option>
        </select>
    </div>

    <div class="mb-4">
        <button wire:click="generateReport" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Generate Report
        </button>
    </div>

    <div>
        @if($data)
            @switch($reportType)
                @case('contact-interactions')
                    @include('reports.partials.contact-interactions', ['data' => $data])
                    @break
                @case('sales-pipeline')
                    @include('reports.partials.sales-pipeline', ['data' => $data])
                    @break
                @case('customer-engagement')
                    @include('reports.partials.customer-engagement', ['data' => $data])
                    @break
            @endswitch
        @else
            <p>No data available. Please generate a report.</p>
        @endif
    </div>
</div>