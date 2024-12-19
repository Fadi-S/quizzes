<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Forms;

class Option extends Model
{
    public $timestamps = false;

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("name")
                ->live()
                ->columnSpan(1)
                ->required(),
            Forms\Components\FileUpload::make("picture")
                ->columnSpan(1)
                ->visibility("public")
                ->directory("questions")
                ->image()
                ->nullable(),

            Forms\Components\Checkbox::make("is_correct")
                ->afterStateHydrated(function ($state, Forms\Get $get, $set) {
                    $answers = collect($get("../../correct_answers") ?: []);

                    $set("is_correct", $answers->contains($get("order")));
                })
                ->visible(
                    fn($get) => QuestionType::tryFrom($get("../../type")) ===
                        QuestionType::Choose,
                ),
        ];
    }
}
