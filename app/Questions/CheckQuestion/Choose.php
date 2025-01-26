<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class Choose implements CheckQuestion
{
    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        $check = collect($question->correct_answers)->contains($answer);

        return new QuestionResponse(
            points: $check ? $question->points : 0,
            response: $answer,
            isCorrect: $check,
        );
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        return $question->correct_answers;
    }
}
