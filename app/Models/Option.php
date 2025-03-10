<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Traits\Linkable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Forms;

class Option extends Model
{
    use Linkable;

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
                ->visibility("private")
                ->directory("options")
                ->image()
                ->visible(
                    fn($get) => QuestionType::tryFrom($get("../../type")) !==
                        QuestionType::Written,
                )
                ->nullable(),

            Forms\Components\Checkbox::make("is_correct")
                ->afterStateHydrated(function ($state, Forms\Get $get, $set) {
                    $answers = collect($get("../../correct_answers") ?: []);

                    $set("is_correct", $answers->contains($get("order")));
                })
                ->visible(
                    fn($get) => QuestionType::tryFrom(
                        $get("../../type"),
                    )?->showIsCorrect() ?? false,
                ),
        ];
    }
}
