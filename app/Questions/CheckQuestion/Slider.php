<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;

readonly class Slider implements CheckQuestion
{
    public function check(Question $question, array|int|string $answer): bool
    {
        return true;
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        // TODO: Implement getCorrectAnswer() method.
    }
}
