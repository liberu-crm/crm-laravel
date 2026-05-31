<?php

namespace App\Filament\App\Pages;

use App\Models\Deal;
use App\Models\Pipeline;
use Filament\Pages\Page;

class VisualPipeline extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected string $view = 'filament.app.pages.visual-pipeline';

    public $pipeline;

    public $stages;

    public $deals;

    public function mount(): void
    {
        $this->pipeline = Pipeline::with('stages')->first();
        $this->stages = $this->pipeline->stages;
        $this->deals = Deal::where('pipeline_id', $this->pipeline->id)->get()->groupBy('stage_id');
    }

    public function updateDealStage($dealId, $newStageId): void
    {
        $deal = Deal::findOrFail($dealId);
        $deal->update(['stage_id' => $newStageId]);
        $this->deals = Deal::where('pipeline_id', $this->pipeline->id)->get()->groupBy('stage_id');
    }
}
