<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class Slider implements CheckQuestion
{
    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        $check = is_array($answer) && $answer === $question->correct_answers;

        return new QuestionResponse(
            $check ? $question->points : 0,
            $answer,
            $check,
        );
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        // TODO: Implement getCorrectAnswer() method.
    }
}
