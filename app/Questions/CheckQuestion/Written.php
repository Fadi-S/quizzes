<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;

readonly class Written implements CheckQuestion
{
    public function check(Question $question, array|int|string $answer): bool
    {
        $question->load("options");

        return $question->options->contains("name", "=", $answer);
    }

    public function getCorrectAnswer(Question $question): string|int|array
    {
        $question->load("options");

        return $question->options->pluck("name")->all();
    }
}
