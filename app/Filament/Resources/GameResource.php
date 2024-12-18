<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->afterStateUpdated(function(string $operation, $state, Forms\Set $set) {
                        if ($operation === 'create') {
                            $set('slug', str($state)->slug(language: null));
                        }
                    })
                    ->live(onBlur: true)
                    ->required(),

                Forms\Components\TextInput::make('slug')
                    ->disabled()
                    ->required(),

                Forms\Components\FileUpload::make('picture')
                    ->visibility('public')
                    ->directory('games')
                    ->columnSpan(2)
                    ->image()
                    ->label("Picture"),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->slug)
                    ->label("Name"),

                Tables\Columns\ImageColumn::make('picture'),

                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime("M j, Y h:i A")
                    ->label("Created At"),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
            'view' => Pages\ViewGame::route('/{record}'),
        ];
    }
}
