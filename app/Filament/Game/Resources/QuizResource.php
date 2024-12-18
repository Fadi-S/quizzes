<?php

namespace App\Filament\Game\Resources;

use App\Enums\QuestionType;
use App\Filament\Game\Resources\QuizResource\Pages;
use App\Filament\Game\Resources\QuizResource\RelationManagers;
use App\Models\Group;
use App\Models\Quiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),

                Forms\Components\Select::make('group_id')
                    ->label('Group')
                    ->options(fn () => Group::pluck('name', 'id')),

                Forms\Components\Repeater::make('questions')
                    ->columnSpan('full')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->visible(fn ($operation) => $operation === "create")
                            ->options(QuestionType::toArray())
                            ->required(),

                        Forms\Components\FileUpload::make('picture')
                            ->visibility('public')
                            ->image()
                            ->nullable(),
//
//                        Forms\Components\ViewField::make('options')
//                            ->view('filament.forms.components.question-options'),

                        Forms\Components\TextInput::make('correct_answers')
                            ->required(),

                        Forms\Components\Repeater::make('options')
                            ->relationship()
                            ->minItems(1)
                            ->orderColumn('order')
                            ->reorderableWithDragAndDrop()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->columnSpan(1)
                                    ->required(),
                                Forms\Components\FileUpload::make('picture')
                                    ->columnSpan(1)
                                    ->visibility('public')
                                    ->image()
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable(),

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
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
