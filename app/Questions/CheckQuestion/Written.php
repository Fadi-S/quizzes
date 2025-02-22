<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class Written implements CheckQuestion
{
    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        $question->load("options");

        $options = $question->options
            ->pluck("name")
            ->map(fn($name) => strtolower($name));

        $check = $options->contains(strtolower($answer));

        return new QuestionResponse(
            points: $check ? 1 : 0,
            response: $answer,
            isCorrect: $check,
        );
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        $question->load("options");

        return $question->options->pluck("name")->all();
    }
}
