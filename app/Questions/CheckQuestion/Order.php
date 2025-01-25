<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;

readonly class Order implements CheckQuestion
{
    public function check(Question $question, array|int|string $answer): bool
    {
        $correctAnswers = $question->correct_answers;

        if (count($correctAnswers) !== count($answer)) {
            return false;
        }

        foreach ($answer as $key => $value) {
            if ($correctAnswers[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        return $question->correct_answers;
    }
}
