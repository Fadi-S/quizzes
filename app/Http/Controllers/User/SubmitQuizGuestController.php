<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use App\Models\Group;
use App\Models\Quiz;
use Illuminate\Http\Request;

class SubmitQuizGuestController extends Controller
{
    public function __invoke($group, $slug, Request $request)
    {
        $quiz = Quiz::query()
            ->whereRelation("group", "slug", "=", $group)
            ->where("slug", $slug)
            ->with("questions.options")
            ->firstOrFail();

        $group = Group::where("slug", $group)->firstOrFail();
        $entity = Entity::createGuest($group);

        \DB::beginTransaction();

        $quizResponse = $quiz->correct($request->get("questions"));

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

        return response([
            "points" => $quizResponse->points,
        ]);
    }
}
