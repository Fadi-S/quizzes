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
        private string $error = "",
    ) {
    }

    public static function error($error): self
    {
        return new self(0, [], [], $error);
    }

    public function hasError(): bool
    {
        return $this->error !== "";
    }

    public function getError(): string
    {
        return $this->error;
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
