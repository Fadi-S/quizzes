<?php

namespace App\Repositories;

use App\Models\Entity;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use App\Models\Group;
use App\Models\Quiz;
use App\Questions\QuizResponse;

class QuizRepository
{
    public function submit(Quiz $quiz, Entity $entity, $questions): QuizResponse
    {
        \DB::beginTransaction();

        $quizResponse = $quiz->correct($questions);

        $entityQuestions = [];
        foreach ($quizResponse->responses as $questionId => $response) {
            $entityQuestions[] = [
                "question_id" => $questionId,
                "entity_id" => $entity->id,
                "answer" => json_encode($response->response),
                "points" => $response->points,
                "is_correct" => $response->isCorrect,
            ];
        }

        EntityQuiz::create([
            "entity_id" => $entity->id,
            "quiz_id" => $quiz->id,
            "points" => $quizResponse->points,
        ]);

        EntityQuestion::upsert(
            $entityQuestions,
            ["question_id", "entity_id"],
            ["answer", "points", "is_correct"],
        );

        \DB::commit();

        return $quizResponse;
    }
}
