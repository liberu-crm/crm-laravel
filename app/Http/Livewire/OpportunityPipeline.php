<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Pipeline;
use App\Models\Deal;
use App\Models\Stage;
use Filament\Tables\Table;

class OpportunityPipeline extends Component
{
    public $pipeline;
    public $stages;
    public $deals;
    public Table $table;

    protected $listeners = ['dealMoved' => 'updateDealStage'];

    public function mount(Table $table)
    {
        $this->table = $table;
        $this->loadPipeline();
    }

    public function loadPipeline()
    {
        $this->pipeline = Pipeline::where('is_active', true)->first();
        $this->stages = $this->pipeline->stages;
        $this->deals = $this->pipeline->getAllDeals();
    }

    public function updateDealStage($dealId, $newStageId)
    {
        $deal = Deal::findOrFail($dealId);
        $newStage = Stage::findOrFail($newStageId);

        $deal->stage()->associate($newStage);
        $deal->save();

        $this->loadPipeline();
    }

    public function render()
    {
        return view('livewire.opportunity-pipeline');
    }
}