<?php

namespace App\Services;

use App\Models\Lead;

class LeadScoringService
{
    public function scoreLeads(Lead $lead): void
    {
        $lead->calculateScore();
    }

    public function recalculateAllScores(): void
    {
        Lead::chunk(100, function ($leads) {
            foreach ($leads as $lead) {
                $this->scoreLeads($lead);
            }
        });
    }
}