<?php

namespace App\Repositories;

use App\Models\Entity;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use App\Models\Quiz;
use App\Questions\QuizResponse;

class QuizRepository
{
    public function submit(Quiz $quiz, Entity $entity, $questions): QuizResponse
    {
        if ($entity->group_id !== $quiz->group_id) {
            return QuizResponse::error(
                "Entity does not belong to the quiz group",
            );
        }

        if (!$quiz->isPublished()) {
            return QuizResponse::error("Quiz is not published yet");
        }

        if (!$quiz->isAvailable()) {
            return QuizResponse::error("Quiz is not available anymore");
        }

        \DB::beginTransaction();

        $quizResponse = $quiz->correct($questions);

        $entityQuiz = EntityQuiz::create([
            "entity_id" => $entity->id,
            "quiz_id" => $quiz->id,
            "points" => $quizResponse->points,
        ]);

        $entityQuestions = [];
        foreach ($quizResponse->responses as $questionId => $response) {
            $entityQuestions[] = [
                "question_id" => $questionId,
                "entity_quiz_id" => $entityQuiz->id,
                "answer" => json_encode($response->response),
                "points" => $response->points,
                "is_correct" => $response->isCorrect,
            ];
        }

        EntityQuestion::upsert(
            $entityQuestions,
            ["question_id", "entity_quiz_id"],
            ["answer", "points", "is_correct"],
        );

        \DB::commit();

        return $quizResponse;
    }
}
