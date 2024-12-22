<?php

namespace App\Questions;

use App\Models\Question;

interface CheckQuestion
{
    public function check(Question $question, string|int|array $answer): bool;

    public function getCorrectAnswer(Question $question): string|int|array;
}
