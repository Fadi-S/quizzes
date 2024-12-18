<?php

namespace App\Enums;

enum QuestionType : int
{
    case Choose = 1;
    case TrueFalse = 2;
    case Written = 3;
    case Order = 4;
    case Match = 5;
    case Slider = 6;

    public static function toArray(): array
    {
        return [
            self::Choose->value => "Choose",
            self::TrueFalse->value => "True - False",
            self::Written->value => "Written",
            self::Order->value => "Order",
            self::Match->value => "Match",
            self::Slider->value => "Slider",
        ];
    }
}
