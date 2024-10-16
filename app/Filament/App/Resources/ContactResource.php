<?php
namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

use App\Services\TwilioService;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ContactResource extends Resource
{
    // ... (existing code remains unchanged)

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... (existing columns remain unchanged)
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('sendSMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->form([
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
                Tables\Actions\Action::make('makeCall')
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
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('bulkSendSMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->form([
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
                Tables\Actions\BulkAction::make('bulkMakeCall')
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

    // ... (rest of the code remains unchanged)
}
