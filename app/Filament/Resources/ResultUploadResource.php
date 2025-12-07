<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Subject;
use Filament\Forms\Form;
use App\Models\ResultRoot;
use Filament\Tables\Table;
use App\Models\SchoolClass;
use App\Models\ResultUpload;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ResultUploadResource\Pages;
use App\Filament\Resources\ResultUploadResource\RelationManagers;

class ResultUploadResource extends Resource
{
    protected static ?string $model = ResultUpload::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';
    protected static ?string $navigationGroup = 'Examinations';
    protected static ?string $navigationLabel = 'Result Uploads';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload Result')
                    ->schema([
                        Select::make('result_root_id')
                            ->label('Result Root')
                            ->required()
                            ->options(ResultRoot::orderBy('created_at', 'desc')->get()->pluck('name', 'id')),
                        Select::make('class_id')
                            ->label('Class')
                            ->required()
                            ->options(SchoolClass::all()->pluck('name', 'id')),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->required()
                            ->options(Subject::all()->pluck('name', 'id')),
                        Section::make('Result CSV')
                            ->description('Upload result csv file')
                            ->schema([
                                FileUpload::make('file_path')
                                ->label('')
                                ->required()
                                ->uploadingMessage('Uploading result...')
                                // ->acceptedFileTypes(['application/csv'])
                                ->directory('result_uploads')
                                ->columnSpanFull(),
                        ])
                    ])->columns(3),
            ]);
    }

    protected function processCsvFile(ResultUpload $record)
    {
        // Path to the CSV file
        $csvPath = Storage::path($record->file_path);

        // Initialize an array to hold the processed data
        $processedData = [];

        // Open and read the CSV file
        if (($handle = fopen($csvPath, 'r')) !== false) {
            // Read header row for column labels
            $headers = fgetcsv($handle);

            // Process each row in the CSV
            while (($data = fgetcsv($handle)) !== false) {
                // Map the data to headers
                $row = array_combine($headers, $data);

                // Extract Student ID
                $studentId = $row['Student_ID'];
                unset($row['Student_ID']); // Remove Student_ID from the score columns

                // Calculate total score
                $totalScore = array_sum(array_map('intval', $row));

                // Structure the student data
                $processedData[$studentId] = [
                    'scores' => $row,
                    'total' => $totalScore,
                ];
            }
            fclose($handle);
        }

        // Save the JSON structure to card_items
        $record->card_items = json_encode($processedData);
        $record->save();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resultRoot.name')
                    ->label('Result Root')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResultUploads::route('/'),
            'create' => Pages\CreateResultUpload::route('/create'),
            'edit' => Pages\EditResultUpload::route('/{record}/edit'),
        ];
    }
}
