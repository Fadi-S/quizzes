<?php

namespace App\Filament\Game\Resources;

use App\Filament\Game\Resources\QuizResource\Pages;
use App\Filament\Game\Resources\QuizResource\RelationManagers;
use App\Models\Group;
use App\Models\Quiz;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = "heroicon-o-pencil";

    public static function form(Form $form): Form
    {
        return $form->schema(Quiz::getForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("group.name")
                    ->label("Group")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make("published_at")
                    ->dateTime("d M Y h:i a")
                    ->label("Published")
                    ->sortable(),

                Tables\Columns\TextColumn::make("questions_count")
                    ->label("# Questions")
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make("group_id")
                    ->label("Group")
                    ->options(Group::pluck("name", "id")),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount("questions");
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListQuizzes::route("/"),
            "create" => Pages\CreateQuiz::route("/create"),
            "edit" => Pages\EditQuiz::route("/{record}/edit"),
        ];
    }
}
