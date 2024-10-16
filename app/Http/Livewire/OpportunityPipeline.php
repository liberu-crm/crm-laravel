<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Pipeline;
use App\Models\Deal;
use App\Models\Stage;

class OpportunityPipeline extends Component
{
    public $pipeline;
    public $stages;
    public $deals;

    protected $listeners = ['dealMoved' => 'updateDealStage'];

    public function mount()
    {
        $this->pipeline = Pipeline::where('is_active', true)->first();
        $this->stages = $this->pipeline->stages;
        $this->deals = $this->pipeline->getAllDeals();
    }

    public function render()
    {
        return view('livewire.opportunity-pipeline');
    }

    public function updateDealStage($dealId, $newStageId)
    {
        $deal = Deal::findOrFail($dealId);
        $newStage = Stage::findOrFail($newStageId);

        $deal->stage()->associate($newStage);
        $deal->save();

        $this->deals = $this->pipeline->getAllDeals();
    }
}