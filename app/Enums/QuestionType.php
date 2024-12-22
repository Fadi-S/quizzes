<?php

namespace App\Enums;

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
}
