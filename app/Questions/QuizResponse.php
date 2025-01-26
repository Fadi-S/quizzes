<?php

namespace App\Questions;

readonly class QuizResponse
{
    /**
     * @param array<QuestionResponse> $responses
     * @param array<bool> $correctAnswers
     */
    public function __construct(
        public int $points,
        public array $responses,
        public array $correctAnswers,
    ) {
    }

    public function toArray(): array
    {
        return [
            "points" => $this->points,
            "checks" => $this->responses,
            "correct_answers" => $this->correctAnswers,
        ];
    }
}
