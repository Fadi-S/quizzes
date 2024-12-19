<?php

namespace App\Filament\Game\Widgets;

use App\Models\ApiKey;
use App\Models\Game;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ApiKeysList extends BaseWidget
{
    protected int|string|array $columnSpan = "full";

    public function table(Table $table): Table
    {
        return $table
            ->actions([
                Tables\Actions\CreateAction::make("generate")->action(
                    fn() => Game::current()->createAPIKey(),
                ),
            ])
            ->query(ApiKey::query())
            ->columns([
                Tables\Columns\TextColumn::make("key")->label("Key"),
                Tables\Columns\TextColumn::make("created_at")->label(
                    "Created At",
                ),
            ]);
    }
}
