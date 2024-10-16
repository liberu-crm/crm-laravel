use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class ContactResource extends Resource
{
    // ... existing code ...

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ... existing fields ...

                Repeater::make('documents')
                    ->relationship('documents')
                    ->schema([
                        FileUpload::make('file')
                            ->disk('public')
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                    ])
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... existing columns ...

                TextColumn::make('documents.count')->label('Documents'),
            ])
            ->actions([
                // ... existing actions ...

                Action::make('uploadDocument')
                    ->label('Upload Document')
                    ->icon('heroicon-o-upload')
                    ->action(function (Contact $record, array $data): void {
                        $record->documents()->create([
                            'file_path' => $data['file'],
                            'version' => 1,
                        ]);
                    })
                    ->form([
                        FileUpload::make('file')
                            ->disk('public')
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                    ]),
            ]);
    }

    // ... existing code ...
}