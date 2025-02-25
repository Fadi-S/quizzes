<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\EntityQuiz;
use App\Models\Group;
use App\Models\Quiz;
use App\Repositories\QuizRepository;
use Illuminate\Http\Request;

class SubmitQuizController extends Controller
{
    public function __construct(private QuizRepository $quizRepo)
    {
    }

    public function __invoke($group, $slug, $entity, Request $request)
    {
        $quiz = Quiz::fromGroupAndSlug($group, $slug);
        if (!$quiz) {
            abort(404);
        }

        $entity = Entity::query()
            ->where("group_id", "=", $quiz->group_id)
            ->where("id", "=", $entity)
            ->firstOrFail();

        $exists = EntityQuiz::query()
            ->where("entity_id", $entity->id)
            ->where("quiz_id", $quiz->id)
            ->exists();

        if ($exists) {
            return response(
                [
                    "message" => "Quiz already submitted",
                ],
                400,
            );
        }

        $quizResponse = $this->quizRepo->submit(
            $quiz,
            $entity,
            $request->get("questions"),
        );

        if ($quizResponse->hasError()) {
            return response(
                [
                    "message" => $quizResponse->getError(),
                ],
                400,
            );
        }

        return response([
            "points" => $quizResponse->points,
        ]);
    }
}
