<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Pipeline;
use App\Models\Deal;
use App\Models\Stage;

class OpportunityPipeline extends Component
{
    public ?int $pipelineId = null;
    public array $stages = [];
    public array $deals = [];

    protected $listeners = ['dealMoved' => 'updateDealStage'];

    public function mount()
    {
        $this->loadPipeline();
    }

    public function loadPipeline()
    {
        $pipeline = Pipeline::where('is_active', true)->first();
        if ($pipeline) {
            $this->pipelineId = $pipeline->id;
            $this->stages = $pipeline->stages->toArray();
            $this->deals = $pipeline->deals->toArray();
        }
    }

    public function updateDealStage($dealId, $newStageId)
    {
        $deal = Deal::findOrFail($dealId);
        Stage::findOrFail($newStageId);

        $deal->stage_id = $newStageId;
        $deal->save();

        $this->loadPipeline();
    }

    public function render()
    {
        return view('livewire.opportunity-pipeline');
    }
}