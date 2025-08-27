<?php
namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\App\Resources\ContactResource\Pages\ListContacts;
use App\Filament\App\Resources\ContactResource\Pages\CreateContact;
use App\Filament\App\Resources\ContactResource\Pages\EditContact;
use App\Filament\App\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

use App\Services\TwilioService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone_number')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                // Add more form fields as needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->searchable(),
                // Add more table columns as needed
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('sendSMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('message')
                            ->label('SMS Message')
                            ->required(),
                    ])
                    ->action(function (Contact $record, array $data, TwilioService $twilioService) {
                        $result = $twilioService->sendSMS($record->phone_number, $data['message']);
                        if ($result) {
                            Notification::make()->title('SMS sent successfully')->success()->send();
                        } else {
                            Notification::make()->title('Failed to send SMS')->danger()->send();
                        }
                    }),
                Action::make('makeCall')
                    ->icon('heroicon-o-phone')
                    ->action(function (Contact $record, TwilioService $twilioService) {
                        $result = $twilioService->makeCall($record->phone_number, route('twilio.twiml.outbound'));
                        if ($result) {
                            Notification::make()->title('Call initiated successfully')->success()->send();
                        } else {
                            Notification::make()->title('Failed to initiate call')->danger()->send();
                        }
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                BulkAction::make('bulkSendSMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Textarea::make('message')
                            ->label('SMS Message')
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data, TwilioService $twilioService) {
                        $successCount = 0;
                        $failCount = 0;
                        foreach ($records as $record) {
                            $result = $twilioService->sendSMS($record->phone_number, $data['message']);
                            $result ? $successCount++ : $failCount++;
                        }
                        Notification::make()
                            ->title("Bulk SMS sent: {$successCount} successful, {$failCount} failed")
                            ->send();
                    }),
                BulkAction::make('bulkMakeCall')
                    ->icon('heroicon-o-phone')
                    ->action(function (Collection $records, TwilioService $twilioService) {
                        $successCount = 0;
                        $failCount = 0;
                        foreach ($records as $record) {
                            $result = $twilioService->makeCall($record->phone_number, route('twilio.twiml.outbound'));
                            $result ? $successCount++ : $failCount++;
                        }
                        Notification::make()
                            ->title("Bulk calls initiated: {$successCount} successful, {$failCount} failed")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
