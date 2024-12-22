<?php

namespace App\Enums;

use App\Questions\CheckQuestion;

enum QuestionType: int
{
    case Choose = 1;
    case Written = 2;
    case Order = 3;
    case Match = 4;
    case Slider = 5;

    public static function toArray(): array
    {
        return [
            self::Choose->value => "Choose",
            self::Written->value => "Written",
            //            self::Order->value => "Order",
            //            self::Match->value => "Match",
            //            self::Slider->value => "Slider",
        ];
    }

    public function showOptions(): bool
    {
        return match ($this) {
            self::Choose, self::Order, self::Match => true,
            self::Written, self::Slider => false,
        };
    }

    public function getChecker(): CheckQuestion
    {
        return match ($this) {
            self::Choose => new CheckQuestion\Choose(),
            self::Written => new CheckQuestion\Written(),
            self::Order => new CheckQuestion\Order(),
            self::Match => new CheckQuestion\Matches(),
            self::Slider => new CheckQuestion\Slider(),
        };
    }
}
