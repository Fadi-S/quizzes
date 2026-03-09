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
        if (is_array($answer)) {
            $answer = collect($answer)
                ->filter(fn($item) => $item !== null && $item !== "")
                ->values()
                ->first();
        }

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
