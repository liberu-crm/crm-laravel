<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Pipeline;
use App\Models\Deal;
use App\Models\Stage;
use Filament\Tables\Table;

class OpportunityPipeline extends Component
{
    public $stages;
    public $deals;
    public Table $table;

    public function mount(Table $table)
    {
        $this->table = $table;
        $this->loadPipeline();
    }

    public function loadPipeline()
    {
        $pipeline = Pipeline::first();
        $this->stages = $pipeline->stages;
        $this->deals = Deal::all()->groupBy('stage_id');
    }

    public function updateDealStage($dealId, $stageId)
    {
        $deal = Deal::findOrFail($dealId);
        $deal->stage_id = $stageId;
        $deal->save();

        $this->loadPipeline();
    }

    public function render()
    {
        return view('livewire.opportunity-pipeline');
    }
}

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