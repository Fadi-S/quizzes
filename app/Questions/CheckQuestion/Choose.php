<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;

readonly class Choose implements CheckQuestion
{
    public function check(Question $question, array|int|string $answer): bool
    {
        return collect($question->correct_answers)->contains($answer);
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        return $question->correct_answers;
    }
}
