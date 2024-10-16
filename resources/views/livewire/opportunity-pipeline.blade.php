<div class="opportunity-pipeline">
    <div class="flex overflow-x-auto">
        @foreach($stages as $stage)
            <div class="flex-shrink-0 w-64 p-4 bg-gray-100 rounded-lg mr-4">
                <h3 class="text-lg font-semibold mb-4">{{ $stage->name }}</h3>
                <div class="stage-deals" 
                     data-stage-id="{{ $stage->id }}"
                     x-data="{ draggable: true }"
                     x-init="
                        new Sortable($el, {
                            group: 'deals',
                            animation: 150,
                            onEnd: function(evt) {
                                @this.call('updateDealStage', evt.item.dataset.dealId, evt.to.dataset.stageId);
                            }
                        })
                     ">
                    @foreach($deals[$stage->id] ?? [] as $deal)
                        <div class="deal-card bg-white p-3 rounded shadow mb-2" data-deal-id="{{ $deal->id }}">
                            <h4 class="font-semibold">{{ $deal->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $deal->value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>