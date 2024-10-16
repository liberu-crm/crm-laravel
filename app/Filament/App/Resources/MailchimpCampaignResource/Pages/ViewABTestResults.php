<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use App\Filament\App\Resources\MailchimpCampaignResource;
use App\Services\MailChimpService;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;

class ViewABTestResults extends Page
{
    protected static string $resource = MailchimpCampaignResource::class;

    protected static string $view = 'filament.app.resources.mailchimp-campaign-resource.pages.view-a-b-test-results';

    public function getTitle(): string
    {
        return "A/B Test Results for Campaign: {$this->record->name}";
    }

    public function table(Table $table): Table
    {
        $mailChimpService = app(MailChimpService::class);
        $results = $mailChimpService->getABTestResults($this->record->id);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('metric')
                    ->label('Metric'),
                Tables\Columns\TextColumn::make('version_a')
                    ->label('Version A'),
                Tables\Columns\TextColumn::make('version_b')
                    ->label('Version B'),
            ])
            ->contents([
                [
                    'metric' => 'Subject Line',
                    'version_a' => $results['subject_a'],
                    'version_b' => $results['subject_b'],
                ],
                [
                    'metric' => 'Opens',
                    'version_a' => $results['opens_a'],
                    'version_b' => $results['opens_b'],
                ],
                [
                    'metric' => 'Open Rate',
                    'version_a' => number_format($results['opens_a'] / $results['emails_sent'] * 100, 2) . '%',
                    'version_b' => number_format($results['opens_b'] / $results['emails_sent'] * 100, 2) . '%',
                ],
                [
                    'metric' => 'Clicks',
                    'version_a' => $results['clicks_a'],
                    'version_b' => $results['clicks_b'],
                ],
                [
                    'metric' => 'Click Rate',
                    'version_a' => number_format($results['clicks_a'] / $results['opens_a'] * 100, 2) . '%',
                    'version_b' => number_format($results['clicks_b'] / $results['opens_b'] * 100, 2) . '%',
                ],
            ]);
    }

    public function getWinnerInfo()
    {
        $mailChimpService = app(MailChimpService::class);
        $results = $mailChimpService->getABTestResults($this->record->id);

        return [
            'winner' => $results['winner'] === 'a' ? 'Version A' : 'Version B',
            'winning_metric' => ucfirst($results['winning_metric']),
            'winning_metric_value' => $results['winning_metric_value'],
        ];
    }
}