<?php

namespace App\Filament\App\Pages;

use App\Services\MailChimpService;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class MailchimpIntegration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected string $view = 'filament.app.pages.mailchimp-integration';

    public ?array $listData = [];

    public ?array $campaignData = [];

    public function mount(MailChimpService $mailchimpService): void
    {
        $this->listData = $mailchimpService->getLists();
    }

    public function createListForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('company')->required(),
                TextInput::make('permission_reminder')->required(),
                TextInput::make('from_name')->required(),
                TextInput::make('from_email')->required()->email(),
            ]);
    }

    public function createList(MailChimpService $mailchimpService): void
    {
        $data = $this->createListForm(new Schema)->getState();
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

    public function createCampaignForm(Schema $schema): Schema
    {
        return $schema
            ->components([
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

    public function createCampaign(MailChimpService $mailchimpService): void
    {
        $data = $this->createCampaignForm(new Schema)->getState();
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

    #[\Override]
    public function getViewData(): array
    {
        return [
            'createListAction' => Action::make('createList')
                ->label('Create List')
                ->schema($this->createListForm(...))
                ->action(fn () => $this->createList(app(MailChimpService::class))),
            'createCampaignAction' => Action::make('createCampaign')
                ->label('Create Campaign')
                ->schema($this->createCampaignForm(...))
                ->action(fn () => $this->createCampaign(app(MailChimpService::class))),
        ];
    }
}
