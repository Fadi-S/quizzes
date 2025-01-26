<?php

namespace App\Filament\Game\Widgets;

use App\Enums\ApiKeyRole;
use App\Models\ApiKey;
use App\Models\Game;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ApiKeysList extends BaseWidget
{
    protected int|string|array $columnSpan = "full";

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make("generate")
                    ->label("Generate Admin API Key")
                    ->icon("heroicon-o-key")
                    ->action(function () {
                        [
                            "key" => $apiKey,
                            "secret" => $secret,
                        ] = Game::current()->createAPIKey(ApiKeyRole::Admin);

                        Notification::make()
                            ->success()
                            ->title("API Key Generated")
                            ->body(
                                "Make sure to copy the secret, it won't be shown again.",
                            )
                            ->send();

                        session()->flash("api-key-display-$apiKey", $secret);
                    }),

                Tables\Actions\Action::make("generate-user")
                    ->label("Generate User API Key")
                    ->icon("heroicon-o-key")
                    ->action(function () {
                        [
                            "key" => $apiKey,
                            "secret" => $secret,
                        ] = Game::current()->createAPIKey(ApiKeyRole::User);

                        Notification::make()
                            ->success()
                            ->title("API Key Generated")
                            ->body(
                                "Make sure to copy the secret, it won't be shown again.",
                            )
                            ->send();

                        session()->flash("api-key-display-$apiKey", $secret);
                    }),
            ])
            ->query(ApiKey::query()->game()->latest())
            ->actions([
                Tables\Actions\DeleteAction::make(
                    "delete",
                )->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make("delete")
                    ->icon("heroicon-o-trash")
                    ->requiresConfirmation(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make("key")->copyable(),
                Tables\Columns\TextColumn::make("role"),
                Tables\Columns\TextColumn::make("secret")
                    ->copyable(
                        fn(ApiKey $record) => (bool) session(
                            "api-key-display-$record->key",
                        ),
                    )
                    ->copyableState(
                        fn(ApiKey $record) => session(
                            "api-key-display-$record->key",
                        ),
                    )
                    ->formatStateUsing(
                        fn(ApiKey $record) => session(
                            "api-key-display-$record->key",
                            "-",
                        ),
                    ),
                Tables\Columns\TextColumn::make("created_at")
                    ->label("Created At")
                    ->dateTime("j M Y - h:i a"),
            ]);
    }
}
