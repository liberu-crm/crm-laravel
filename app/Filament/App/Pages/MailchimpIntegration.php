<?php

namespace App\Filament\App\Pages;

use App\Services\MailChimpService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class MailchimpIntegration extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static string $view = 'filament.app.pages.mailchimp-integration';

    public ?array $listData = [];
    public ?array $campaignData = [];

    public function mount(MailChimpService $mailchimpService)
    {
        $this->listData = $mailchimpService->getLists();
    }

    public function createListForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('company')->required(),
                TextInput::make('permission_reminder')->required(),
                TextInput::make('from_name')->required(),
                TextInput::make('from_email')->required()->email(),
            ]);
    }

    public function createList(MailChimpService $mailchimpService)
    {
        $data = $this->createListForm(new Form())->getState();
        $result = $mailchimpService->createList(
            $data['name'],
            $data['company'],
            $data['permission_reminder'],
            $data['from_name'],
            $data['from_email']
        );

        if ($result) {
            Notification::make()
                ->title('List created successfully')
                ->success()
                ->send();
            $this->listData = $mailchimpService->getLists();
        } else {
            Notification::make()
                ->title('Failed to create list')
                ->danger()
                ->send();
        }
    }

    public function createCampaignForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('list_id')
                    ->label('Recipient List')
                    ->options($this->listData)
                    ->required(),
                TextInput::make('subject')->required(),
                TextInput::make('from_name')->required(),
                TextInput::make('reply_to')->required()->email(),
                RichEditor::make('html_content')->required(),
            ]);
    }

    public function createCampaign(MailChimpService $mailchimpService)
    {
        $data = $this->createCampaignForm(new Form())->getState();
        $result = $mailchimpService->createCampaign(
            $data['list_id'],
            $data['subject'],
            $data['from_name'],
            $data['reply_to'],
            $data['html_content']
        );

        if ($result) {
            Notification::make()
                ->title('Campaign created successfully')
                ->success()
                ->send();
            $this->campaignData = $mailchimpService->getCampaigns();
        } else {
            Notification::make()
                ->title('Failed to create campaign')
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        return [
            'createListAction' => Action::make('createList')
                ->label('Create List')
                ->form($this->createListForm(...))
                ->action(fn () => $this->createList(app(MailChimpService::class))),
            'createCampaignAction' => Action::make('createCampaign')
                ->label('Create Campaign')
                ->form($this->createCampaignForm(...))
                ->action(fn () => $this->createCampaign(app(MailChimpService::class))),
        ];
    }
}