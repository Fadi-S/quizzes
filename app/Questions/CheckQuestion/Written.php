<?php

namespace App\Questions\CheckQuestion;

use App\Models\Question;
use App\Questions\CheckQuestion;
use App\Questions\QuestionResponse;

readonly class Written implements CheckQuestion
{
    private function normalizeAnswer(string $answer): string
    {
        $answer = strtolower(trim($answer));

        $numbersLetter = [
            "one" => "1",
            "two" => "2",
            "three" => "3",
            "four" => "4",
            "five" => "5",
            "six" => "6",
            "seven" => "7",
            "eight" => "8",
            "nine" => "9",
            "ten" => "10",
        ];

        $numbersArabic = [
            "١" => "1",
            "٢" => "2",
            "٣" => "3",
            "٤" => "4",
            "٥" => "5",
            "٦" => "6",
            "٧" => "7",
            "٨" => "8",
            "٩" => "9",
        ];

        $answer = strtr($answer, $numbersLetter);
        return strtr($answer, $numbersArabic);
    }

    public function check(
        Question $question,
        array|int|string $answer,
    ): QuestionResponse {
        $question->load("options");

        $options = $question->options
            ->pluck("name")
            ->map(fn($name) => $this->normalizeAnswer($name));

        $check = $options->contains($this->normalizeAnswer($answer));

        return new QuestionResponse(
            points: $check ? $question->points : 0,
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
