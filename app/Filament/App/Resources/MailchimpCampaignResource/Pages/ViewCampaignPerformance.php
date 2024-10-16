<?php

namespace App\Filament\App\Resources\MailchimpCampaignResource\Pages;

use App\Filament\App\Resources\MailchimpCampaignResource;
use App\Services\MailChimpService;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;

class ViewCampaignPerformance extends Page
{
    protected static string $resource = MailchimpCampaignResource::class;

    protected static string $view = 'filament.app.resources.mailchimp-campaign-resource.pages.view-campaign-performance';

    public function getTitle(): string
    {
        return "Campaign Performance: {$this->record->name}";
    }

    public function table(Table $table): Table
    {
        $mailChimpService = app(MailChimpService::class);
        $report = $mailChimpService->getCampaignReport($this->record->id);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('metric')
                    ->label('Metric'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value'),
            ])
            ->contents([
                [
                    'metric' => 'Emails Sent',
                    'value' => $report['emails_sent'],
                ],
                [
                    'metric' => 'Unique Opens',
                    'value' => $report['unique_opens'],
                ],
                [
                    'metric' => 'Open Rate',
                    'value' => number_format($report['open_rate'] * 100, 2) . '%',
                ],
                [
                    'metric' => 'Clicks',
                    'value' => $report['clicks'],
                ],
                [
                    'metric' => 'Click Rate',
                    'value' => number_format($report['click_rate'] * 100, 2) . '%',
                ],
                [
                    'metric' => 'Unsubscribes',
                    'value' => $report['unsubscribes'],
                ],
                [
                    'metric' => 'Bounce Rate',
                    'value' => number_format($report['bounce_rate'] * 100, 2) . '%',
                ],
            ]);
    }
}