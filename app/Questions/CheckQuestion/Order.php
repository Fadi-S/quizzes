<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class Order implements CheckQuestion
{
    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        $check = true;

        $correctAnswers = $question->correct_answers;

        if (count($correctAnswers) !== count($answer)) {
            $check = false;
        } else {
            foreach ($answer as $key => $value) {
                if ($correctAnswers[$key] !== $value) {
                    $check = false;
                    break;
                }
            }
        }

        return new QuestionResponse(
            points: $check ? 1 : 0,
            response: $answer,
            isCorrect: $check,
        );
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        return $question->correct_answers;
    }
}
