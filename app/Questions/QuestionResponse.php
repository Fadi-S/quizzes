<?php

namespace App\Questions;

readonly class QuestionResponse
{
    public function __construct(
        public int $points,
        public array|string|int $response,
        public bool $isCorrect,
    ) {
    }

    public static function noResponse(): self
    {
        return new self(0, "", false);
    }

    public function toArray(): array
    {
        return [
            "points" => $this->points,
            "response" => $this->response,
            "correct" => $this->isCorrect,
        ];
    }
}
