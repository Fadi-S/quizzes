<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class MultipleChoose implements CheckQuestion
{
    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        if (!is_array($answer)) {
            $answer = [$answer];
        }
        $answer = collect($answer);
        $correctAnswers = collect($question->correct_answers);

        $totalCorrectCount = $correctAnswers->count();
        if ($totalCorrectCount === 0) {
            return new QuestionResponse(
                points: 0,
                response: $answer,
                isCorrect: false,
            );
        }

        $correctCount = $correctAnswers->intersect($answer)->count();
        $points = ($correctCount / $totalCorrectCount) * $question->points;

        $check = $totalCorrectCount === $correctCount;

        return new QuestionResponse(
            points: $check ? $points : 0,
            response: $answer,
            isCorrect: $check,
        );
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        return $question->correct_answers;
    }
}
