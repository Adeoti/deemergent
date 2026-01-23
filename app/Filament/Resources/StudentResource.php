<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Student;
use App\Models\Branch; 
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\User;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'HRM';
    protected static bool $isDiscoverable = false;

    
    public static function getEloquentQuery(): Builder
    {
        // Eager load the user relationship
        
        return parent::getEloquentQuery()->with('user');
    }

    public static function form(Form $form): Form
    {

        
        return $form
        ->schema([
            Forms\Components\Section::make('User Details')
            ->schema([
                TextInput::make('user.name') 
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('user.email') 
                    ->label('Email Address')
                    ->required()
                    ->email()
                    ->visibleOn('create')
                    ->maxLength(255)
                    ->unique(User::class, 'email'),

                TextInput::make('password')
                    ->label('Password')
                    ->revealable()
                    ->password()
                    ->nullable() // Make it optional
                    ->minLength(8),

                TextInput::make('user.address') // Correctly reference the user relationship
                    ->label('Address')
                    ->nullable(),

                FileUpload::make('user.passport') // Correctly reference the user relationship
                    ->label('Passport Photo')
                    ->nullable()
                    ->disk('public')
                    ->columnSpanFull()
                    ->directory('passports'),
            ])->columns(2),


                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(Branch::all()->pluck('name', 'id'))
                            ->required(),

                        Select::make('school_class_id')
                            ->relationship('class', 'name')
                            ->required()
                            ->label('Class'),

                        TextInput::make('roll_number')
                            ->label('Registration Number')
                            ->unique(Student::class, 'roll_number', ignoreRecord: true)
                            ->required(),
                        DatePicker::make('admission_date')
                            ->label('Admission Date')
                            ->required(),
                    ])->columns(2),

                    Section::make('Parent Details')
                        ->schema([
                            TextInput::make('parent_contact')
                                ->label('Parent Contact')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('guardian_name')
                                ->label('Guardian Name')
                                ->required(),
                            TextInput::make('guardian_phone')
                                ->label('Guardian Phone')
                                ->required(),
                        ])->columns(3),
            ]);
    }

 
    public static function table(Table $table): Table
    {
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Full Name')
                    
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\ImageColumn::make('user.passport')
                    ->label('Passport Photo')
                   
                    ->circular(),
                
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('roll_number')
                    ->label('Registration Number')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.branch.name')
                    ->label('Branch')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                // You can add filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any related models here
           
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
